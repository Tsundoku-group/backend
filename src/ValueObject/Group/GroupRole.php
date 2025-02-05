<?php

namespace App\ValueObject\Group;

use InvalidArgumentException;

final class GroupRole
{
    private const ADMIN = 'admin';
    private const MEMBER = 'member';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::allRoles(), true)) {
            throw new InvalidArgumentException("Invalid role type: $value");
        }
        $this->value = $value;
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function member(): self
    {
        return new self(self::MEMBER);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(GroupRole $other): bool
    {
        return $this->value === $other->value;
    }

    public function canDeleteGroup(): bool
    {
        return self::ADMIN === $this->value;
    }

    public function canManageMembers(): bool
    {
        return self::ADMIN === $this->value;
    }

    public function canPostContent(): bool
    {
        return in_array($this->value, [self::ADMIN, self::MEMBER], true);
    }

    public function canViewGroup(): bool
    {
        return true;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAdmin(): bool
    {
        return self::ADMIN === $this->value;
    }

    public function isMember(): bool
    {
        return self::MEMBER === $this->value;
    }

    public static function allRoles(): array
    {
        return [self::ADMIN, self::MEMBER];
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
