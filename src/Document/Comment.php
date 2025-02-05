<?php

namespace App\Document;

use App\Repository\CommentRepository;
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'comments', repositoryClass: CommentRepository::class)]
class Comment
{
    #[ODM\Id]
    private string $id;

    #[ODM\Field(type: 'string')]
    private string $postId;

    #[ODM\Field(type: 'string')]
    private string $authorId;

    #[ODM\Field(type: 'string')]
    private string $content;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    #[ODM\Field(type: 'string')]
    private ?string $parentId = null;

    #[ODM\Field(type: 'collection')]
    private array $children = [];

    public function __construct(string $postId, string $authorId, string $content, ?string $parentId = null)
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

    public function getPostId(): string
    {
        return $this->postId;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt->format('Y-m-d\TH:i:s\Z');
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
