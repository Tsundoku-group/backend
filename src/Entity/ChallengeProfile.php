<?php

namespace App\Entity;

use App\Enum\ChallengeStatusEnum;
use App\Repository\ChallengeProfileRepository;
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
    private ?Challenge $challenge;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'challengeProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(type: 'json')]
    private array $progress = [];

    #[ORM\Column(type: 'string', enumType: ChallengeStatusEnum::class)]
    private ChallengeStatusEnum $status = ChallengeStatusEnum::PENDING;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): static
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

    public function getProgress(): array
    {
        return $this->progress;
    }

    public function setProgress(array $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getStatus(): ChallengeStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ChallengeStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }
}
