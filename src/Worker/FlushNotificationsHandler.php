<?php

namespace App\Worker;

use App\Message\FlushNotificationsMessage;
use App\Service\Redis\RedisNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FlushNotificationsHandler
{
    public function __construct(
        private RedisNotificationService $redisNotificationService,
        private EntityManagerInterface   $entityManager
    ) {}

    public function __invoke(FlushNotificationsMessage $message): void
    {
        $receiverId = $message->getReceiverId();

        if ($receiverId) {
            $notifications = $this->redisNotificationService->getNotifications($receiverId);

            if (!empty($notifications)) {
                $this->entityManager->beginTransaction();
                try {
                    foreach ($notifications as $notification) {
                        $this->redisNotificationService->flushNotificationToDatabase($notification);
                    }
                    $this->entityManager->commit();
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                }
            }
        } else {
            $allNotifications = $this->redisNotificationService->getAllNotifications();

            if (!empty($allNotifications)) {
                $this->entityManager->beginTransaction();
                try {
                    foreach ($allNotifications as $notifications) {
                        foreach ($notifications as $notification) {
                            $this->redisNotificationService->flushNotificationToDatabase($notification);
                        }
                    }
                    $this->entityManager->commit();
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                }
            }
        }
    }
}
