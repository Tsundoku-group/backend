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
            'profileId' => $actorId,
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
            'type' => NotificationTypeEnum::LIKE->value,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redisClient->getClient()->rpush($notificationKey, (array)$notificationData);
    }

    public function flushNotificationsToDatabase(): void
    {
        $client = $this->redisClient->getClient();
        $keys = $client->keys("notifications:*");

        foreach ($keys as $key) {
            $recipientId = str_replace("notifications:", "", $key);
            $notifications = $client->lrange($key, 0, -1);

            foreach ($notifications as $notificationJson) {
                $notificationData = json_decode($notificationJson, true);
                $recipient = $this->profileRepository->find($notificationData['recipientId']);
                $actor = $this->profileRepository->find($notificationData['profileId']);

                if (!$recipient || !$actor) {
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
            $client->del($key);
        }
    }
}