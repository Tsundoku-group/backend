<?php
namespace App\Entity;

use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Ramsey\Uuid\Doctrine\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: "react")]
class React
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, cascade: ["remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Profile $profile;

    #[ORM\Column(type: "string", length: 50, enumType: ReactTypeEnum::class)]
    private ReactTypeEnum $reactType;

    #[ORM\Column(type: "string", length: 50, enumType: ResourceTypeEnum::class)]
    private ResourceTypeEnum $resourceType;

    #[ORM\Column(type: "uuid")]
    private string $resourceId;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(Profile $profile, string $resourceId, ResourceTypeEnum $resourceType, ReactTypeEnum $reactType)
    {
        $this->profile = $profile;
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

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
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

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}