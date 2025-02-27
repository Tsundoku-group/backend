<?php

namespace App\Worker;

use App\Message\ResetThresholdMessage;
use App\Service\ResetThresholdService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ResetThresholdHandler
{
    public function __construct(private ResetThresholdService $resetThresholdService) {}

    public function __invoke(ResetThresholdMessage $message): void
    {
        $this->resetThresholdService->resetThresholds();
    }
}