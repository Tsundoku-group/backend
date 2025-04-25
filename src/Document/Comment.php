<?php

namespace App\Document;

use App\Repository\CommentRepository;
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'comments', repositoryClass: CommentRepository::class)]
class Comment
{
    /**
     * @var string|null Set by Doctrine
     */
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'integer')]
    private int $postId;

    #[ODM\Field(type: 'integer')]
    private int $authorId;

    #[ODM\Field(type: 'string')]
    private string $content;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    #[ODM\Field(type: 'date')]
    private DateTime $updatedAt;

    #[ODM\Field(type: 'string')]
    private ?string $parentId = null;

    #[ODM\Field(type: 'collection')]
    private array $children = [];

    public function __construct(int $postId, int $authorId, string $content, ?string $parentId = null)
    {
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->content = $content;
        $this->createdAt = new DateTime();
        $this->parentId = $parentId;
        $this->children = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt->format('Y-m-d\TH:i:s\Z');
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(string $childId): void
    {
        if (!in_array($childId, $this->children)) {
            $this->children[] = $childId;
        }
    }
}
