<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\ValueObject\Post\PostStatus;
use App\ValueObject\Post\PostVisibility;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Group $group = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Profile $author;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $visibility;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->status = PostStatus::ACTIVE;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthor(): Profile
    {
        return $this->author;
    }

    public function setAuthor(Profile $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->slug = strtolower(str_replace(' ', '-', $title));
        $this->markAsUpdated();
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getVisibility(): PostVisibility
    {
        return PostVisibility::fromString($this->visibility);
    }

    public function setVisibility(PostVisibility $visibility): void
    {
        $this->visibility = $visibility->getValue();
        $this->markAsUpdated();
    }

    public function getStatus(): PostStatus
    {
        return PostStatus::fromString($this->status);
    }

    public function setStatus(PostStatus $status): void
    {
        $this->status = $status->getValue();
        $this->markAsUpdated();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }
}