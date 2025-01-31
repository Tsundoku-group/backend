<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use App\Enum\GroupRoleEnum;
use InvalidArgumentException;

class GroupRoleType extends Type
{
    public const NAME = 'group_role';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return "group_role";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return GroupRoleEnum::tryFrom($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if (!$value instanceof GroupRoleEnum) {
            throw new InvalidArgumentException("Invalid ENUM value.");
        }
        return $value->value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}