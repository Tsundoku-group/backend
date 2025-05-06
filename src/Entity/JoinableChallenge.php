<?php

namespace App\Entity;

use App\Enum\ChallengeFrequencyEnum;
use App\Repository\JoinableChallengeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: JoinableChallengeRepository::class)]
class JoinableChallenge extends Challenge
{
    #[ORM\Column(type: 'string', enumType: ChallengeFrequencyEnum::class, nullable: true)]
    private ?ChallengeFrequencyEnum $frequency = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $constraints = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    public function getFrequency(): ?ChallengeFrequencyEnum
    {
        return $this->frequency;
    }

    public function setFrequency(?ChallengeFrequencyEnum $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getConstraints(): ?array
    {
        return $this->constraints;
    }

    public function setConstraints(?array $constraints): self
    {
        $this->constraints = $constraints;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
}