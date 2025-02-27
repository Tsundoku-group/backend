<?php

namespace App\Scheduler;

use App\Message\FlushNotificationsMessage;
use App\Config\RedisClientConfig;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
readonly class FlushNotificationsSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private RedisClientConfig $redis
    ) {}

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('30 minutes', new FlushNotificationsMessage()))
            ->add(RecurringMessage::every('24 hours', function () {
                $keys = $this->redis->getClient()->keys("notification_flush_threshold:*");
                foreach ($keys as $key) {
                    $this->redis->getClient()->set($key, 10);
                }
            }));
    }
}