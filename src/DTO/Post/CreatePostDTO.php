<?php

namespace App\DTO\Post;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePostDTO
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser 255 caractères.')]
    public string $title;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    public string $content;

    #[Assert\NotBlank(message: "L'auteur du post est obligatoire.")]
    #[Assert\Positive(message: "L'ID de l'auteur doit être valide.")]
    public int $authorId;

    #[Assert\NotBlank(message: 'Le groupe est obligatoire.')]
    #[Assert\Positive(message: "L'ID du groupe doit être valide.")]
    public int $groupId;

    #[Assert\Choice(choices: ['public', 'private'], message: "La visibilité doit être 'public' ou 'private'.")]
    public string $visibility;

    public function __construct(string $title, string $content, int $authorId, int $groupId, string $visibility)
    {
        $this->title = $title;
        $this->content = $content;
        $this->authorId = $authorId;
        $this->groupId = $groupId;
        $this->visibility = $visibility;
    }
}
