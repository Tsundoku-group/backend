<?php

namespace App\ValueObject\Post;

use InvalidArgumentException;

final class PostStatus
{
    public const ACTIVE = 'active';
    private const ARCHIVED = 'archived';
    private const DELETED = 'deleted';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::allStatuses(), true)) {
            throw new InvalidArgumentException("Statut de post invalide: $value");
        }
        $this->value = $value;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function archived(): self
    {
        return new self(self::ARCHIVED);
    }

    public static function deleted(): self
    {
        return new self(self::DELETED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this->value;
    }

    public function isArchived(): bool
    {
        return self::ARCHIVED === $this->value;
    }

    public function isDeleted(): bool
    {
        return self::DELETED === $this->value;
    }

    public function canBeEdited(): bool
    {
        return $this->isActive();
    }

    public function canBeDeleted(): bool
    {
        return !$this->isDeleted();
    }

    public function canBeRestored(): bool
    {
        return !$this->isArchived() && !$this->isDeleted();
    }

    public static function allStatuses(): array
    {
        return [self::ACTIVE, self::ARCHIVED, self::DELETED];
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(PostStatus $status): bool
    {
        return $this->value === $status->getValue();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
