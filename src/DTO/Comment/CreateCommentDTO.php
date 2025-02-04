<?php

namespace App\DTO\Comment;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCommentDTO
{
    #[Assert\NotBlank(message: "postId is required")]
    #[Assert\Uuid(message: "Invalid postId format")]
    public string $postId;

    #[Assert\NotBlank(message: "authorId is required")]
    #[Assert\Uuid(message: "Invalid authorId format")]
    public string $authorId;

    #[Assert\NotBlank(message: "Content is required")]
    #[Assert\Length(min: 3, max: 1000, minMessage: "Content must be at least 3 characters", maxMessage: "Content must be at most 1000 characters")]
    public string $content;

    #[Assert\Uuid(message: "Invalid parentId format")]
    public ?string $parentId = null;

    public function __construct(string $postId, string $authorId, string $content, ?string $parentId = null)
    {
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->content = $content;
        $this->parentId = $parentId;
    }
}