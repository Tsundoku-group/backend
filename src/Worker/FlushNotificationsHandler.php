<?php

namespace App\Worker;

use App\Message\FlushNotificationsMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FlushNotificationsHandler
{
    public function __construct()
    {
    }

    public function __invoke(FlushNotificationsMessage $message): void
    {
    }
}