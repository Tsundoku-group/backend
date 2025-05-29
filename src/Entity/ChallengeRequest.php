<?php

namespace App\Entity;

use App\Entity\Challenge;
use App\Entity\Profile;
use App\Enum\ChallengeRequestStatusEnum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ChallengeRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class, inversedBy: 'challengeRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Challenge $challenge;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'challengeRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $profile;

    #[ORM\Column(enumType: ChallengeRequestStatusEnum::class)]
    private ChallengeRequestStatusEnum $status = ChallengeRequestStatusEnum::PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $joinAt = null;

    public function __construct(Challenge $challenge, Profile $profile)
    {
        $this->challenge = $challenge;
        $this->profile = $profile;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): Challenge
    {
        return $this->challenge;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getStatus(): ChallengeRequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ChallengeRequestStatusEnum $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();

        if (ChallengeRequestStatusEnum::ACCEPTED === $status) {
            $this->joinAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getJoinAt(): ?DateTimeImmutable
    {
        return $this->joinAt;
    }

    public function setJoinAt(?DateTimeImmutable $joinAt): self
    {
        $this->joinAt = $joinAt;

        return $this;
    }
}