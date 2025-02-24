<?php

namespace App\Service\Redis;

use App\Config\RedisClientConfig;
use App\Entity\Notification;
use App\Enum\NotificationTypeEnum;
use App\Repository\ProfileRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class RedisNotificationService
{
    public function __construct(
        private RedisClientConfig $redisClient,
        private EntityManagerInterface $entityManager,
        private ProfileRepository $profileRepository,
        private LoggerInterface $logger
    ) {
    }

    public function addNotificationToCache(string $recipientId, string $actorId, string $resourceType, ?string $resourceId): void
    {
        $notificationKey = "notifications:{$recipientId}";
        $notificationData = json_encode([
            'recipientId' => $recipientId,
            'actorId' => $actorId,
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
            'type' => NotificationTypeEnum::LIKE->value,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redisClient->getClient()->rpush($notificationKey, (array)$notificationData);

        $this->logger->info("📌 Notification ajoutée en cache", [
            'recipientId' => $recipientId,
            'actorId' => $actorId,
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
            'type' => NotificationTypeEnum::LIKE->value
        ]);
    }

    public function flushNotificationsToDatabase(): void
    {
        $client = $this->redisClient->getClient();
        $keys = $client->keys("notifications:*");

        if (empty($keys)) {
            $this->logger->info("ℹ️ Aucune notification à flusher.");
            return;
        }

        $this->logger->info("📢 Début du flush des notifications...");

        foreach ($keys as $key) {
            $recipientId = str_replace("notifications:", "", $key);
            $notifications = $client->lrange($key, 0, -1);

            if (empty($notifications)) {
                $this->logger->info("⚠️ Aucune notification pour {$recipientId}, suppression de la clé.");
                $client->del($key);
                continue;
            }

            $this->entityManager->beginTransaction();

            try {
                foreach ($notifications as $notificationJson) {
                    $notificationData = json_decode($notificationJson, true);

                    $recipient = $this->profileRepository->find($notificationData['recipientId']);
                    $actor = $this->profileRepository->find($notificationData['actorId']);

                    if (!$recipient || !$actor) {
                        $this->logger->warning("⚠️ Impossible de récupérer le profil ou l'acteur pour la notification", [
                            'recipientId' => $notificationData['recipientId'],
                            'actorId' => $notificationData['actorId']
                        ]);
                        continue;
                    }

                    $notification = new Notification(
                        $recipient,
                        $actor,
                        NotificationTypeEnum::from($notificationData['type']),
                        $notificationData['resourceId'] ?? null
                    );

                    $this->entityManager->persist($notification);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();
                $client->del($key);

                $this->logger->info("✅ Notifications flushées pour {$recipientId}");
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error("❌ Échec du flush des notifications", [
                    'error' => $e->getMessage(),
                    'recipientId' => $recipientId
                ]);
            }
        }
    }
}