<?php

namespace App\Enum;

enum ChallengeFrequencyEnum: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case ONCE = 'once';
}
