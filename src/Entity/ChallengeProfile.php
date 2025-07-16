<?php

namespace App\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ChallengeProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class, inversedBy: 'challengeProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private Challenge $challenge;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'challengeProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $role;

    #[ORM\Column(type: 'integer')]
    private int $progress;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $joinAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt = null;

    public function __construct(Challenge $challenge, Profile $profile, ?string $role = "participant")
    {
        $this->challenge = $challenge;
        $this->profile = $profile;
        $this->role = $role;
        $this->progress = 0;
        $this->joinAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): Challenge
    {
        return $this->challenge;
    }
    public function setChallenge(Challenge $challenge): static
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
        $this->markAsUpdated();
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getJoinAt(): DateTimeImmutable
    {
        return $this->joinAt;
    }

    public function setJoinAt(DateTimeImmutable $joinAt): self
    {
        $this->joinAt = $joinAt;
        $this->markAsUpdated();

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isAdmin(): bool
    {
        return 'admin' === $this->role;
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }
}
