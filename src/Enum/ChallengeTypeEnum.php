<?php

namespace App\Enum;

enum ChallengeTypeEnum: string
{
    case COMMUNITY = 'community';
    case PREDEFINED = 'predefined';
    case CUSTOMISED = 'customised';
}