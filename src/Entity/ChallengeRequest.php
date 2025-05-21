<?php

namespace App\Entity;

use App\Entity\Challenge;
use App\Entity\Profile;
use App\Enum\ChallengeRequestStatusEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ChallengeRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class, inversedBy: 'challengeRequests')]
    private ?Challenge $challenge;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'challengeRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(type: 'string', enumType: ChallengeRequestStatusEnum::class)]
    private ChallengeRequestStatusEnum $status = ChallengeRequestStatusEnum::PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): self
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

    public function getStatus(): ChallengeRequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ChallengeRequestStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}