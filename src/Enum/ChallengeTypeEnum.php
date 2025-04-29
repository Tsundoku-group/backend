<?php

namespace App\Enum;

enum ChallengeTypeEnum: string
{
    case PREDEFINED = 'predefined';
    case COMMUNITY = 'community';
    case CUSTOMISED = 'customised';
}
