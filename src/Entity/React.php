<?php

namespace App\Entity;

use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'react')]
class React
{
    /**
     * @var string|null Set by Doctrine
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $actor;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $receiver;

    #[ORM\Column(type: 'string', length: 50, enumType: ReactTypeEnum::class)]
    private ReactTypeEnum $reactType;

    #[ORM\Column(type: 'string', length: 50, enumType: ResourceTypeEnum::class)]
    private ResourceTypeEnum $resourceType;

    #[ORM\Column(type: 'string')]
    private string $resourceId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(Profile $actor, Profile $receiver, string $resourceId, ResourceTypeEnum $resourceType, ReactTypeEnum $reactType)
    {
        $this->actor = $actor;
        $this->receiver = $receiver;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->reactType = $reactType;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getActor(): Profile
    {
        return $this->actor;
    }

    public function setActor(Profile $actor): void
    {
        $this->actor = $actor;
    }

    public function getReceiver(): Profile
    {
        return $this->receiver;
    }

    public function setReceiver(Profile $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getType(): ReactTypeEnum
    {
        return $this->reactType;
    }

    public function setType(ReactTypeEnum $reactType): void
    {
        $this->reactType = $reactType;
    }

    public function getResourceType(): ResourceTypeEnum
    {
        return $this->resourceType;
    }

    public function setResourceType(ResourceTypeEnum $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
