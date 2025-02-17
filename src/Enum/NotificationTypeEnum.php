<?php

namespace App\Enum;

enum NotificationTypeEnum: string
{
    case LIKE = 'like';
    case COMMENT = 'comment';
    case FOLLOW = 'follow';
}