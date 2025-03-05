<?php

namespace App\Scheduler;

use App\Message\FlushNotificationsMessage;
use App\Message\ResetThresholdMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class FlushNotificationsSchedule implements ScheduleProviderInterface
{
    public function __construct()
    {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('30 minutes', new FlushNotificationsMessage()))
            ->add(RecurringMessage::every('24 hours', new ResetThresholdMessage()));
    }
}
