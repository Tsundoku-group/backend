<?php

namespace App\Enum;

enum ChallengeStatusEnum: string
{
    case PENDING  = 'pending';
    case ONGOING  = 'ongoing';
    case SUCCESS  = 'success';
    case FAILED   = 'failed';
    case CANCELED = 'canceled';
}
