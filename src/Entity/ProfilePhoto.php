<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ProfilePhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: "profilePhotos")]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(name: 'url', length: 255, unique: true)]
    #[Assert\Url(message: 'Veuillez fournir une URL valide.')]
    #[Assert\NotBlank(message: 'L\'URL ne peut pas être vide.')]
    private string $url;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    #[Assert\Choice(choices: [self::TYPE_PROFILE, self::TYPE_COVER], message: 'Le type doit être valide.')]
    private string $type;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public const TYPE_PROFILE = 'profile';
    public const TYPE_COVER = 'cover';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_PROFILE, self::TYPE_COVER], true)) {
            throw new \InvalidArgumentException('Type invalide');
        }
        $this->type = $type;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): self
    {
        $this->isActive = true;

        return $this;
    }

    public function deactivate(): self
    {
        $this->isActive = false;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
