<?php

namespace App\Schedule;

use App\Message\DeleteUserMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

class DeleteUserSchedule implements ScheduleProviderInterface {
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::every('24hours', new DeleteUserMessage())
        );
    }
}
