<?php

namespace App\DTO\Post;

use Symfony\Component\Validator\Constraints as Assert;

class UpdatePostDTO
{
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser 255 caractères.')]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    public string $content;

    #[Assert\Length(max: 20, maxMessage: 'Le statut ne doit pas dépasser 20 caractères.')]
    public ?string $status;

    #[Assert\Choice(choices: ['public', 'private'], message: "La visibilité doit être 'public' ou 'private'.")]
    public string $visibility;

    public function __construct(?string $title, string $content, ?string $status, string $visibility)
    {
        $this->title = $title;
        $this->content = $content;
        $this->status = $status;
        $this->visibility = $visibility;
    }
}
