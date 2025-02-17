<?php

namespace App\Entity;

use App\Enum\NotificationTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: "notifications")]
#[ORM\Index(name: "idx_notifications_recipient", columns: ["recipient_id"])]
#[ORM\Index(name: "idx_notifications_actor", columns: ["actor_id"])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, cascade: ["remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Profile $recipient;

    #[ORM\ManyToOne(targetEntity: Profile::class, cascade: ["remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Profile $actor;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "uuid", nullable: true)]
    private ?UuidInterface $resourceId = null;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $isRead = false;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?DateTimeImmutable $isReadAt = null;

    public function __construct(Profile $recipient, Profile $actor, NotificationTypeEnum $type, ?UuidInterface $resourceId = null)
    {
        $this->recipient = $recipient;
        $this->actor = $actor;
        $this->type = $type->value;
        $this->resourceId = $resourceId;
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

    public function getRecipient(): Profile
    {
        return $this->recipient;
    }

    public function setRecipient(Profile $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getActor(): Profile
    {
        return $this->actor;
    }

    public function setActor(Profile $actor): void
    {
        $this->actor = $actor;
    }

    public function getType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::from($this->type);
    }

    public function setType(NotificationTypeEnum $type): void
    {
        $this->type = $type->value;
    }

    public function getResourceId(): ?UuidInterface
    {
        return $this->resourceId;
    }

    public function setResourceId(?UuidInterface $resourceId): void
    {
        $this->resourceId = $resourceId;
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