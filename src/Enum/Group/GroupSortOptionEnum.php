<?php

namespace App\Enum\Group;

enum GroupSortOptionEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
    case MEMBERS = 'members';

    public static function getSortQuery(string $sort): string
    {
        return match ($sort) {
            self::MEMBERS->value => 'membersCount DESC',
            self::NEWEST->value => 'g.createdAt DESC',
            self::OLDEST->value => 'g.createdAt ASC',
            default => 'g.createdAt DESC',
        };
    }
}