<?php

namespace App\Service\Redis;

use App\Config\RedisClientConfig;
use App\Entity\Notification;
use App\Enum\NotificationTypeEnum;
use App\Repository\NotificationRepository;
use App\Repository\ProfileRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class RedisNotificationService
{
    public function __construct(
        private RedisClientConfig      $redis,
        private EntityManagerInterface $entityManager,
        private ProfileRepository      $profileRepository,
        #[Autowire(service: 'monolog.logger.notifications')]
        private LoggerInterface        $logger,
        private NotificationRepository $notificationRepository,
    )
    {
    }

    public function addNotificationToCache(string $receiverId, string $actorId, string $notificationTypeEnum, ?string $resourceId): void
    {
        $notificationKey = "notifications:{$receiverId}";
        $notificationData = json_encode([
            'receiverId' => $receiverId,
            'actorId' => $actorId,
            'resourceId' => $resourceId,
            'isRead' => false,
            'notificationType' => $notificationTypeEnum,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redis->getClient()->rpush($notificationKey, (array)$notificationData);

        if ($this->redis->getClient()->llen($notificationKey) >= 50) {
            $this->flushNotificationsToDatabase();
        }
    }

    public function getNotificationsFromCache(string $receiverId): array
    {
        $reactionKey = "notifications:{$receiverId}";
        $reactionsJson = $this->redis->getClient()->lrange($reactionKey, 0, -1);

        return array_map(fn($json) => json_decode($json, true), $reactionsJson);
    }

    public function flushNotificationsToDatabase(?string $receiverId = null, bool $onlyRead = false): void
    {
        $client = $this->redis->getClient();
        $keys = $receiverId ? ["notifications:{$receiverId}"] : $client->keys('notifications:*');

        if (empty($keys)) {
            $this->logger->info('ℹ️ Aucune notification à flusher.');
            return;
        }

        $this->logger->info('📢 Début du flush des notifications...');

        foreach ($keys as $key) {
            $receiverId = str_replace('notifications:', '', $key);
            $notifications = $client->lrange($key, 0, -1);

            if (empty($notifications)) {
                $this->logger->info("⚠️ Aucune notification pour {$receiverId}, suppression de la clé.");
                $client->del($key);
                continue;
            }

            $this->entityManager->beginTransaction();

            try {
                foreach ($notifications as $notificationJson) {
                    $notificationData = json_decode($notificationJson, true);

                    if ($onlyRead && empty($notificationData['isRead'])) {
                        continue;
                    }

                    $recipient = $this->profileRepository->find($notificationData['recipientId']);
                    $actor = $this->profileRepository->find($notificationData['actorId']);

                    if (!$recipient || !$actor) {
                        $this->logger->warning("⚠️ Impossible de récupérer le profil ou l'acteur pour la notification", [
                            'recipientId' => $notificationData['recipientId'],
                            'actorId' => $notificationData['actorId'],
                        ]);
                        continue;
                    }

                    $existingNotification = $this->notificationRepository->findOneBy([
                        'recipient' => $recipient,
                        'profile' => $actor,
                        'type' => NotificationTypeEnum::from($notificationData['notificationType']),
                        'resourceId' => $notificationData['resourceId'] ?? null
                    ]);

                    if ($existingNotification) {
                        if ($notificationData['isRead'] === true) {
                            $existingNotification->setIsRead(true);
                            $existingNotification->setIsReadAt(new DateTimeImmutable());
                            $this->logger->info("✅ Notification mise à jour comme lue en BDD", [
                                'notificationId' => $existingNotification->getId()
                            ]);
                        }
                    } else {
                        $notification = new Notification(
                            $recipient,
                            $actor,
                            NotificationTypeEnum::from($notificationData['notificationType']),
                            $notificationData['resourceId'] ?? null
                        );

                        $notification->setIsRead($notificationData['isRead'] ?? false);
                        if ($notification->isRead()) {
                            $notification->setIsReadAt(new DateTimeImmutable());
                        }

                        $this->entityManager->persist($notification);
                        $this->logger->info("📝 Nouvelle notification enregistrée en BDD", [
                            'recipientId' => $notificationData['recipientId'],
                            'actorId' => $notificationData['actorId'],
                            'type' => $notificationData['notificationType'],
                            'resourceId' => $notificationData['resourceId'] ?? null,
                            'isRead' => $notification->isRead()
                        ]);
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->commit();
                $client->del($key);

                $this->logger->info("✅ Notifications flushées en BDD pour {$receiverId}");
            } catch (Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error('❌ Échec du flush des notifications', [
                    'error' => $e->getMessage(),
                    'recipientId' => $receiverId,
                ]);
            }
        }
    }
}
