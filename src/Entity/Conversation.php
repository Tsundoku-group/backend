<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'conversations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'conversationsParticipants')]
    private Collection $participants;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isArchived;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $archivedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isMuted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $mutedUntil = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMessageAt = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->isArchived = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getIsArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $IsArchived): self
    {
        $this->isArchived = $IsArchived;

        if ($IsArchived === true) {
            $this->archivedAt = new \DateTime();
        } else {
            $this->archivedAt = null;
        }

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeInterface
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeInterface $archivedAt): self
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

    public function getMutedUntil(): ?\DateTimeInterface
    {
        return $this->mutedUntil;
    }

    public function setMutedUntil(?\DateTimeInterface $mutedUntil): self
    {
        $this->mutedUntil = $mutedUntil;
        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeInterface
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(\DateTimeInterface $lastMessageAt): self
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }
}