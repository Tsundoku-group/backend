<?php

namespace App\DTO\Post;

use Symfony\Component\Validator\Constraints as Assert;

class DeletePostDTO
{
    #[Assert\NotBlank(message: "L'identifiant du post est requis.")]
    #[Assert\Type(type: 'integer', message: "L'identifiant du post doit être un nombre entier.")]
    public int $postId;

    #[Assert\NotBlank(message: "L'identifiant de l'auteur est requis.")]
    #[Assert\Type(type: 'integer', message: "L'identifiant de l'auteur doit être un nombre entier.")]
    public int $authorId;

    public function __construct(int $postId, int $authorId)
    {
        $this->postId = $postId;
        $this->authorId = $authorId;
    }
}