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
        private ProfileRepository      $profileRepository,
        private MessageBusInterface    $bus,
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function addNotificationToCache(
        string $receiverId,
        string $actorId,
        string $notificationTypeEnum,
        ?string $resourceId,
        string $resourceTypeEnum
    ): void {
        $notificationKey = "notifications:{$receiverId}";
        $thresholdKey = "notification_flush_threshold:{$receiverId}";

        if (!$this->redis->getClient()->exists($thresholdKey)) {
            $this->redis->getClient()->set($thresholdKey, 10);
            error_log("✅ [Redis] Initialisation du seuil pour {$receiverId} à 10");
        }

        $threshold = (int) $this->redis->getClient()->get($thresholdKey);

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
        $this->redis->getClient()->expire($notificationKey, 86400);

        $notificationCount = $this->redis->getClient()->llen($notificationKey);

        if ($notificationCount >= $threshold) {
            $this->bus->dispatch(new FlushNotificationsMessage($receiverId));

            $newThreshold = min(100, (int) ceil($threshold * 1.5));
            $this->redis->getClient()->set($thresholdKey, $newThreshold);
        }
    }

    public function getNotificationsByWeek(string $receiverId, int $weeksAgo): array
    {
        $notificationKey = "notifications:{$receiverId}";
        $notificationsJson = $this->redis->getClient()->lrange($notificationKey, 0, -1);

        $notifications = array_map(fn($json) => json_decode($json, true), $notificationsJson);

        $startDate = (new DateTimeImmutable("now - {$weeksAgo} weeks"))
            ->modify('Monday this week')->setTime(0, 0);
        $endDate = (clone $startDate)->modify('+6 days')->setTime(23, 59, 59);

        $filteredNotifications = array_filter($notifications, function ($notif) use ($startDate, $endDate) {
            $notifDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $notif['createdAt']);
            return $notifDate >= $startDate && $notifDate <= $endDate;
        });

        if (empty($filteredNotifications)) {
            $filteredNotifications = $this->notificationRepository->fetchNotificationsFromDatabase($receiverId, $startDate, $endDate);
            $notificationsToCache = array_map(fn($notification) => $this->formatNotification($notification), $filteredNotifications);

            foreach ($notificationsToCache as $notif) {
                $this->redis->getClient()->rpush($notificationKey, (array)json_encode($notif));
            }
        }

        return array_values($filteredNotifications);
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

        $notifications = array_map(fn($notification) => $this->formatNotification($notification), $notificationsFromDB);

        foreach ($notifications as $notification) {
            $this->redis->getClient()->rpush($notificationKey, (array)json_encode($notification));
        }

        return $notifications;
    }

    public function getAllNotifications(): array
    {
        $keys = $this->redis->getClient()->keys('notifications:*');
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

        $existingNotification = $this->notificationRepository->findOneBy([
            'receiver' => $receiver,
            'actor' => $actor,
            'resourceId' => $notificationData['resourceId'] ?? null,
            'resourceType' => $notificationData['resourceType'],
            'notificationType' => $notificationData['notificationType'],
        ]);

        if ($existingNotification) {
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
            $notification->setIsReadAt(new DateTimeImmutable());
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    private function formatNotification(Notification $notification): array
    {
        return [
            'receiverId' => $notification->getReceiver()->getId(),
            'actorId' => $notification->getActor()->getId(),
            'resourceId' => $notification->getResourceId(),
            'resourceType' => $notification->getResourceType(),
            'notificationType' => $notification->getNotificationType(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
