<?php

namespace App\Entity;

use App\Enum\RequestStatusEnum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group_request')]
class GroupRequest
{
    /**
     * @var int|null Set by Doctrine
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $profile;

    #[ORM\Column(length: 20, enumType: RequestStatusEnum::class)]
    private RequestStatusEnum $status = RequestStatusEnum::PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $joinAt = null;

    public function __construct(Group $group, Profile $profile)
    {
        $this->group = $group;
        $this->profile = $profile;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getStatus(): RequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(RequestStatusEnum $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();

        if (RequestStatusEnum::ACCEPTED === $status) {
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
