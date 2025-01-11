<?php

namespace App\Entity;

use App\Repository\FriendshipRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\Table(name: "friendship", indexes: [
    new ORM\Index(name: "idx_requester", columns: ["requester"]),
    new ORM\Index(name: "idx_receiver", columns: ["receiver"]),
    new ORM\Index(name: "idx_status", columns: ["status"]),
    new ORM\Index(name: "idx_requester_receiver_status", columns: ["requester", "receiver", "status"])
])]
class Friendship
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'sentFriendships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $requester = null;

    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'receivedFriendships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $receiver = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $friendAt = null;

    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRequester(): ?Profile
    {
        return $this->requester;
    }

    public function setRequester(?Profile $requester): static
    {
        $this->requester = $requester;

        return $this;
    }

    public function getReceiver(): ?Profile
    {
        return $this->receiver;
    }

    public function setReceiver(?Profile $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getFriendAt(): ?DateTimeImmutable
    {
        return $this->friendAt;
    }

    public function setFriendAt(?DateTimeImmutable $friendAt): static
    {
        $this->friendAt = $friendAt;

        return $this;
    }
}
