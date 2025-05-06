<?php

namespace App\Entity;

use App\Enum\ChallengeStatusEnum;
use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeTypeEnum;
use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;

#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
#[ORM\Table(name: 'challenge')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'challenge_type', type: 'string')]
#[DiscriminatorMap([
    'base' => Challenge::class,
    'joinable' => JoinableChallenge::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', enumType: ChallengeTypeEnum::class)]
    private ChallengeTypeEnum $type;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column(type: 'string', enumType: ChallengeStatusEnum::class)]
    protected ChallengeStatusEnum $status;

    #[ORM\Column(type: 'string', enumType: ChallengeActionTypeEnum::class)]
    protected ChallengeActionTypeEnum $actionType;

    #[ORM\Column(type: 'string', enumType: ChallengeContentTypeEnum::class)]
    protected ChallengeContentTypeEnum $contentType;

    #[ORM\Column(type: 'integer')]
    protected int $quantity;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeProfile::class, orphanRemoval: true)]
    protected Collection $challengeProfiles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function setType(ChallengeTypeEnum $type): self
    {
        $this->type = $type;
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

    public function getStatus(): ChallengeStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ChallengeStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getActionType(): ChallengeActionTypeEnum
    {
        return $this->actionType;
    }

    public function setActionType(ChallengeActionTypeEnum $actionType): self
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getContentType(): ChallengeContentTypeEnum
    {
        return $this->contentType;
    }

    public function setContentType(ChallengeContentTypeEnum $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
