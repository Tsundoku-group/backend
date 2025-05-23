<?php

namespace App\Entity;

use App\Enum\ChallengeStatusEnum;
use App\Enum\ChallengeTypeEnum;
use App\ValueObject\ChallengeConstraint;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: ChallengeTypeEnum::class)]
    private ChallengeTypeEnum $type;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: ChallengeStatusEnum::class)]
    private ChallengeStatusEnum $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'challenges')]
    private Profile $creator;

    #[ORM\Embedded(class: ChallengeConstraint::class)]
    private ChallengeConstraint $constraint;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeRequest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $challengeRequests;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeProfile::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $challengeProfiles;

    public function __construct()
    {
        $this->challengeRequests = new ArrayCollection();
        $this->challengeProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ChallengeTypeEnum
    {
        return $this->type;
    }

    public function setType(ChallengeTypeEnum $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getCreator(): Profile
    {
        return $this->creator;
    }

    public function setCreator(Profile $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getConstraint(): ChallengeConstraint
    {
        return $this->constraint;
    }

    public function setConstraint(ChallengeConstraint $constraint): static
    {
        $this->constraint = $constraint;

        return $this;
    }

    public function getChallengeRequests(): Collection
    {
        return $this->challengeRequests;
    }

    public function addChallengeRequest(ChallengeRequest $challengeRequest): self
    {
        if (!$this->challengeRequests->contains($challengeRequest)) {
            $this->challengeRequests[] = $challengeRequest;
            $challengeRequest->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengeRequest(ChallengeRequest $challengeRequest): self
    {
        if ($this->challengeRequests->removeElement($challengeRequest)) {
            // set the owning side to null (unless already changed)
            if ($challengeRequest->getChallenge() === $this) {
                $challengeRequest->setChallenge(null);
            }
        }

        return $this;
    }

    public function getChallengeProfiles(): Collection
    {
        return $this->challengeProfiles;
    }

    public function addChallengeProfile(ChallengeProfile $challengeProfile): self
    {
        if (!$this->challengeProfiles->contains($challengeProfile)) {
            $this->challengeProfiles[] = $challengeProfile;
            $challengeProfile->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengeProfile(ChallengeProfile $challengeProfile): self
    {
        if ($this->challengeProfiles->removeElement($challengeProfile)) {
            // set the owning side to null (unless already changed)
            if ($challengeProfile->getChallenge() === $this) {
                $challengeProfile->setChallenge(null);
            }
        }

        return $this;
    }
}
