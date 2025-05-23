<?php
// src/Entity/Badge.php

namespace App\Entity;

use App\ValueObject\BadgeStyle;
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

    #[ORM\Embedded(class: BadgeStyle::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?BadgeStyle $style;
    
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

    public function getStyle(): BadgeStyle
    {
        return $this->style;
    }

    public function setStyle(?BadgeStyle $style): static
    {
        $this->style = $style;
        return $this;
    }
}
