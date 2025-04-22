<?php

namespace App\DTO\Comment;

use Symfony\Component\Validator\Constraints as Assert;

class GetCommentDTO
{
    #[Assert\NotBlank(message: 'postId is required')]
    #[Assert\Uuid(message: 'Invalid postId format')]
    public int $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }
}
