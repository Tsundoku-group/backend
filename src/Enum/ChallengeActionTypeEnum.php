<?php

namespace App\Enum;

enum ChallengeActionTypeEnum: string
{
    case READ = 'read';
    case WRITE = 'write';
    case HAVE = 'have';
}