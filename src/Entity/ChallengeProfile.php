<?php

namespace App\Entity;

use App\Enum\ChallengeProfileRoleEnum;
use App\Enum\ChallengeProfileStatusEnum;
use App\Repository\ChallengeProfileRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeProfileRepository::class)]
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

    #[ORM\Column(type: 'string', enumType: ChallengeProfileStatusEnum::class)]
    private ChallengeProfileStatusEnum $status;

    #[ORM\Column(type: 'string', enumType: ChallengeProfileRoleEnum::class)]
    private ChallengeProfileRoleEnum $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $joinedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->joinedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(Challenge $challenge): self
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getStatus(): ChallengeProfileStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ChallengeProfileStatusEnum $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        if ($status === ChallengeProfileStatusEnum::SUCCEEDED || $status === ChallengeProfileStatusEnum::FAILED) {
            $this->completedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function getRole(): ChallengeProfileRoleEnum
    {
        return $this->role;
    }

    public function setRole(ChallengeProfileRoleEnum $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getJoinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
