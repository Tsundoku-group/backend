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
        private NotificationRepository $notificationRepository,
    )
    {
    }

    public function addNotificationToCache(string $receiverId, string $actorId, string $notificationTypeEnum, ?string $resourceId, string $resourceTypeEnum): void
    {
        $notificationKey = "notifications:{$receiverId}";
        $notificationData = json_encode([
            'receiverId' => $receiverId,
            'actorId' => $actorId,
            'resourceId' => $resourceId,
            'resourceType' => $resourceTypeEnum,
            'isRead' => false,
            'notificationType' => $notificationTypeEnum,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redis->getClient()->rpush($notificationKey, (array)$notificationData);
    }

    public function getNotifications(string $receiverId): array
    {
        $notificationKey = "notifications:{$receiverId}";
        $notificationsJson = $this->redis->getClient()->lrange($notificationKey, 0, -1);

        if (!empty($notificationsJson)) {
            return array_map(fn($json) => json_decode($json, true), $notificationsJson);
        }

        $notificationsFromDB = $this->notificationRepository->findBy(
            ['receiver' => $receiverId],
            ['createdAt' => 'DESC'],
            10
        );

        $notifications = array_map(fn($notification) => [
            'receiverId' => $notification->getReceiver()->getId(),
            'actorId' => $notification->getActor()->getId(),
            'resourceId' => $notification->getResourceId(),
            'resourceType' => $notification->getResourceType(),
            'notificationType' => $notification->getType(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $notificationsFromDB);

        foreach ($notifications as $notification) {
            $this->redis->getClient()->rpush($notificationKey, (array)json_encode($notification));
        }

        return $notifications;
    }

    public function markNotificationsAsReadInCache(string $receiverId): void
    {
        $notifications = $this->getNotifications($receiverId);

        foreach ($notifications as &$notification) {
            $notification['isRead'] = true;
        }

        $this->redis->getClient()->del("notifications:{$receiverId}");
        foreach ($notifications as &$notification) {
            $this->redis->getClient()->rpush("notifications:{$receiverId}", (array)json_encode($notification));
        }
    }
}