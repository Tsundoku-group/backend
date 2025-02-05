<?php

namespace App\ValueObject\Post;

use InvalidArgumentException;

final class PostVisibility
{
    private const PUBLIC = 'public';
    private const PRIVATE = 'private';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::allValues(), true)) {
            throw new InvalidArgumentException("Type de visibilité invalide: $value");
        }
        $this->value = $value;
    }

    public static function public(): self
    {
        return new self(self::PUBLIC);
    }

    public static function private(): self
    {
        return new self(self::PRIVATE);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPublic(): bool
    {
        return self::PUBLIC === $this->value;
    }

    public function isPrivate(): bool
    {
        return self::PRIVATE === $this->value;
    }

    public function canBeChangedTo(PostVisibility $newVisibility): bool
    {
        if ($this->isPublic() && $newVisibility->isPrivate()) {
            return false;
        }

        return true;
    }

    public static function allValues(): array
    {
        return [self::PUBLIC, self::PRIVATE];
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(PostVisibility $visibility): bool
    {
        return $this->value === $visibility->getValue();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
