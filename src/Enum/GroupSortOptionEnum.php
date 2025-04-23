<?php

namespace App\Enum;

enum GroupSortOptionEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
    case MEMBERS = 'members';
    case ACTIVE = 'active';

    public static function getSortQuery(string $sort): string
    {
        return match ($sort) {
            self::MEMBERS->value => 'membersCount DESC',
            self::NEWEST->value => 'g.createdAt DESC',
            self::OLDEST->value => 'g.createdAt ASC',
            self::ACTIVE->value => 'activityScore DESC',
            default => 'g.createdAt DESC',
        };
    }
}
