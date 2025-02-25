<?php

namespace App\Worker;

use App\Message\FlushReactionsMessage;
use App\Service\Redis\RedisReactService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FlushReactionsHandler
{
    public function __construct(private RedisReactService $redisReactService)
    {
    }

    public function __invoke(FlushReactionsMessage $message): void
    {
        $this->redisReactService->flushAllReactionsToDatabase();
    }
}