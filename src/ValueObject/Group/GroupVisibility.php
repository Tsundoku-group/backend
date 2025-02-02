<?php

namespace App\ValueObject\Group;

final class GroupVisibility
{
    private const PRIVATE = 'private';
    private const PUBLIC = 'public';
    private const UNIQUE_PUBLIC_GROUP = 'fil-d-actualite';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::allValues(), true)) {
            throw new \InvalidArgumentException("Invalid visibility type: $value");
        }
        $this->value = $value;
    }

    public static function private(): self
    {
        return new self(self::PRIVATE);
    }

    public static function public(): self
    {
        return new self(self::PUBLIC);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPublic(): bool
    {
        return $this->value === self::PUBLIC;
    }

    public function isPrivate(): bool
    {
        return $this->value === self::PRIVATE;
    }

    public function isUniquePublicGroup(string $slug): bool
    {
        return $this->isPublic() && $slug === self::UNIQUE_PUBLIC_GROUP;
    }

    public static function allValues(): array
    {
        return [self::PRIVATE, self::PUBLIC];
    }
}