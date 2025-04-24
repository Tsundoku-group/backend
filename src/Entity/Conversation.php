<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    /**
     * @var int|null Set by Doctrine
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'conversations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $createdBy = null;

    #[ORM\ManyToMany(targetEntity: Profile::class, inversedBy: 'conversationsParticipants')]
    private Collection $participants;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isArchived;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $archivedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isMuted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $mutedUntil = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastMessageAt = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->isArchived = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?Profile
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Profile $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, Profile>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Profile $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function getIsArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $IsArchived): self
    {
        $this->isArchived = $IsArchived;

        return $this;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?DateTimeImmutable $archivedAt): self
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }

    public function getIsMuted(): bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): self
    {
        $this->isMuted = $isMuted;

        return $this;
    }

    public function getMutedUntil(): ?DateTimeInterface
    {
        return $this->mutedUntil;
    }

    public function setMutedUntil(?DateTimeInterface $mutedUntil): self
    {
        $this->mutedUntil = $mutedUntil;

        return $this;
    }

    public function getLastMessageAt(): ?DateTimeInterface
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(DateTimeInterface $lastMessageAt): self
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }
}
