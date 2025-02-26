<?php

namespace App\Entity;

use App\Enum\NotificationTypeEnum;
use App\Enum\ResourceTypeEnum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_notifications_profile', columns: ['actor_id'])]
#[ORM\Index(name: 'idx_notifications_receiver', columns: ['receiver_id'])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $actor;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $receiver;

    #[ORM\Column(type: 'string', length: 50)]
    private string $notificationType;

    #[ORM\Column(type: 'string', nullable: true)]
    private string $resourceId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $resourceType;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $isReadAt = null;

    public function __construct(Profile $receiver, Profile $actor, NotificationTypeEnum $notificationType, string $resourceId, ResourceTypeEnum $resourceType)
    {
        $this->receiver = $receiver;
        $this->actor = $actor;
        $this->notificationType = $notificationType->value;
        $this->resourceId = $resourceId;
        $this->resourceType = $resourceType->value;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getReceiver(): Profile
    {
        return $this->receiver;
    }

    public function setReceiver(Profile $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getActor(): Profile
    {
        return $this->actor;
    }

    public function setActor(Profile $actor): void
    {
        $this->actor = $actor;
    }

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::from($this->notificationType);
    }

    public function setNotificationType(NotificationTypeEnum $notificationType): void
    {
        $this->notificationType = $notificationType->value;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceType(): ResourceTypeEnum
    {
        return ResourceTypeEnum::from($this->resourceType);
    }

    public function setResourceType(ResourceTypeEnum $resourceType): void
    {
        $this->resourceType = $resourceType->value;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): void
    {
        $this->isRead = $isRead;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getIsReadAt(): ?DateTimeImmutable
    {
        return $this->isReadAt;
    }

    public function setIsReadAt(?DateTimeImmutable $isReadAt): void
    {
        $this->isReadAt = $isReadAt;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
        $this->isReadAt = new DateTimeImmutable();
    }
}
