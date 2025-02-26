<?php

namespace App\Worker;

use App\Message\FlushReactionsMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FlushReactionsHandler
{
    public function __construct()
    {
    }

    public function __invoke(FlushReactionsMessage $message): void
    {
    }
}