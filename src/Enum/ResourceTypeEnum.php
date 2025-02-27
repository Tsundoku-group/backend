<?php

namespace App\Enum;

enum ResourceTypeEnum: string
{
    case POST = 'POST';
    case COMMENT = 'COMMENT';
    case FOLLOW = 'FOLLOW';
}
