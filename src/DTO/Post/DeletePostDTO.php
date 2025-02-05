<?php

namespace App\DTO\Post;

use Symfony\Component\Validator\Constraints as Assert;

class DeletePostDTO
{
    #[Assert\NotBlank(message: "L'identifiant du post est requis.")]
    #[Assert\Type(type: 'integer', message: "L'identifiant du post doit être un nombre entier.")]
    public int $postId;

    #[Assert\NotBlank(message: "L'identifiant de l'editeur est requis.")]
    #[Assert\Type(type: 'integer', message: "L'identifiant de l'éditeur doit être un nombre entier.")]
    public int $editorId;

    public function __construct(int $postId, int $editorId)
    {
        $this->postId = $postId;
        $this->editorId = $editorId;
    }
}
