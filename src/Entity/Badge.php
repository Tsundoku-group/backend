<?php
// src/Entity/Badge.php

namespace App\Entity;

use App\Enum\BadgeTypeEnum;
use App\Enum\ChallengeTypeEnum;
use App\Repository\BadgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
#[ORM\Table(name: 'badge')]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: ChallengeTypeEnum::class)]
    private ChallengeTypeEnum $forChallengeType;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $config = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $showcase = false;

    #[ORM\OneToMany(mappedBy: 'badge', targetEntity: Challenge::class)]
    private Collection $challenges;

    public function __construct(
        ChallengeTypeEnum $forChallengeType,
        string $name,
        ?string $imagePath = null,
        ?array $config = null
    ) {
        $this->forChallengeType = $forChallengeType;
        $this->name = $name;
        $this->imagePath = $imagePath;
        $this->config = $config;
        $this->challenges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForChallengeType(): ChallengeTypeEnum
    {
        return $this->forChallengeType;
    }

    public function setForChallengeType(ChallengeTypeEnum $forChallengeType): self
    {
        $this->forChallengeType = $forChallengeType;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function isShowcase(): bool
    {
        return $this->showcase;
    }

    public function setShowcase(bool $showcase): self
    {
        $this->showcase = $showcase;
        return $this;
    }

    /**
     * @return Collection<int, Challenge>
     */
    public function getChallenges(): Collection
    {
        return $this->challenges;
    }
}
