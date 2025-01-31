<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use App\Enum\GroupVisibility;
use InvalidArgumentException;

class GroupVisibilityType extends Type
{
    public const NAME = 'group_visibility';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return "group_visibility";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return GroupVisibility::tryFrom($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if (!$value instanceof GroupVisibility) {
            throw new InvalidArgumentException("Invalid ENUM value.");
        }
        return $value->value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}