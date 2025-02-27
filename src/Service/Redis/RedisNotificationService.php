<?php

namespace App\Service\Redis;

use App\Config\RedisClientConfig;
use App\Entity\Notification;
use App\Enum\NotificationTypeEnum;
use App\Enum\ResourceTypeEnum;
use App\Message\FlushNotificationsMessage;
use App\Repository\NotificationRepository;
use App\Repository\ProfileRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class RedisNotificationService
{
    public function __construct(
        private RedisClientConfig      $redis,
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
        private ProfileRepository $profileRepository,
        private MessageBusInterface $bus,
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
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
        if ($this->redis->getClient()->llen($notificationKey) >= 5) {
            $this->bus->dispatch(new FlushNotificationsMessage());
        }
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

    public function getAllNotifications(): array
    {
        $keys = $this->redis->getClient()->keys("notifications:*");
        $allNotifications = [];

        if (empty($keys)) {
            return [];
        }

        foreach ($keys as $key) {
            $receiverId = str_replace('notifications:', '', $key);
            $notifications = $this->redis->getClient()->lrange($key, 0, -1);

            if (!empty($notifications)) {
                $allNotifications[$receiverId] = array_map(fn($json) => json_decode($json, true), $notifications);
            }
        }

        return $allNotifications;
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

    public function flushNotificationToDatabase(array $notificationData): void
    {
        $receiver = $this->profileRepository->find($notificationData['receiverId']);
        $actor = $this->profileRepository->find($notificationData['actorId']);

        if (!$receiver || !$actor) {
            return;
        }

        $notification = new Notification(
            $receiver,
            $actor,
            NotificationTypeEnum::from($notificationData['notificationType']),
            $notificationData['resourceId'] ?? null,
            ResourceTypeEnum::from($notificationData['resourceType'])
        );

        $notification->setIsRead($notificationData['isRead']);
        if ($notification->isRead()) {
            $notification->setIsReadAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function clearNotificationsCache(string $receiverId): void
    {
        $keys = $this->redis->getClient()->keys("notifications:*");

        if (!empty($keys)) {
            $this->redis->getClient()->del($keys);
        }
    }
}