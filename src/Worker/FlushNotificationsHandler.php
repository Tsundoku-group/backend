<?php

namespace App\Worker;

use App\Message\FlushNotificationsMessage;
use App\Service\Redis\RedisNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FlushNotificationsHandler
{
    public function __construct(private RedisNotificationService $redisNotificationService)
    {
    }

    public function __invoke(FlushNotificationsMessage $message): void
    {
        $this->redisNotificationService->flushNotificationsToDatabase();
    }
}