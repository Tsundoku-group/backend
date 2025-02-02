<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\ValueObject\Post\PostStatus;
use App\ValueObject\Post\PostVisibility;
use DateTimeImmutable;
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

    #[ORM\Column(length: 20)]
    private string $visibility;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct(Profile $author, string $title, string $content, PostVisibility $visibility, ?Group $group = null)
    {
        $this->author = $author;
        $this->title = $title;
        $this->content = $content;
        $this->slug = strtolower(str_replace(' ', '-', $title));
        $this->visibility = (string) $visibility;
        $this->status = PostStatus::ACTIVE;
        $this->group = $group;
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

    public function getGroup(): ?Group
    {
        return $this->group;
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

    public function getVisibility(): PostVisibility
    {
        return PostVisibility::fromString($this->visibility);
    }

    public function setVisibility(PostVisibility $visibility): void
    {
        $this->visibility = (string) $visibility;
        $this->markAsUpdated();
    }

    public function getStatus(): PostStatus
    {
        return PostStatus::fromString($this->status);
    }

    public function setStatus(PostStatus $status): void
    {
        $this->status = (string) $status;
        $this->markAsUpdated();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}