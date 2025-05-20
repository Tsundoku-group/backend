<?php
// src/Entity/Badge.php

namespace App\Entity;

use App\Enum\ChallengeTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: ChallengeProfile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ChallengeProfile $challengeProfile;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $awardedAt;

    #[ORM\Column(enumType: ChallengeTypeEnum::class)]
    private ChallengeTypeEnum $forChallengeType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallengeProfile(): ChallengeProfile
    {
        return $this->challengeProfile;
    }

    public function setChallengeProfile(ChallengeProfile $challengeProfile): static
    {
        $this->challengeProfile = $challengeProfile;

        return $this;
    }

    public function getAwardedAt(): \DateTimeImmutable
    {
        return $this->awardedAt;
    }

    public function setAwardedAt(\DateTimeImmutable $awardedAt): static
    {
        $this->awardedAt = $awardedAt;

        return $this;
    }

    public function getForChallengeType(): ChallengeTypeEnum
    {
        return $this->forChallengeType;
    }

    public function setForChallengeType(ChallengeTypeEnum $forChallengeType): static
    {
        $this->forChallengeType = $forChallengeType;

        return $this;
    }
}
