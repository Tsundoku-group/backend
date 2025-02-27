<?php

namespace App\Worker;

use App\Message\FlushNotificationsMessage;
use App\Service\Redis\RedisNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FlushNotificationsHandler
{
    public function __construct(
        private RedisNotificationService $redisNotificationService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(FlushNotificationsMessage $message): void
    {
        $allNotifications = $this->redisNotificationService->getAllNotifications();

        if (empty($allNotifications)) {
            return;
        }

        $this->entityManager->beginTransaction();

        try {
            foreach ($allNotifications as $receiverId => $notifications) {
                foreach ($notifications as $notification) {
                    $this->redisNotificationService->flushNotificationToDatabase($notification);
                }
                $this->redisNotificationService->clearNotificationsCache($receiverId);
            }

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
        }
    }
}
