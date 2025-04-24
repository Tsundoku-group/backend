<?php

namespace App\DTO\Conversation;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateConversationDTO
{
    #[Assert\NotBlank(message: 'Le nom d’utilisateur est requis.')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le nom d’utilisateur doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom d’utilisateur ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $username;

    #[Assert\NotBlank(message: 'Les participants sont requis.')]
    #[Assert\All([
        new Assert\Type(type: 'integer', message: 'Les ID des participants doivent être des entiers.'),
        new Assert\Positive(message: 'Les ID des participants doivent être positifs.'),
    ])]
    public array $participants;

    public function __construct(array $data)
    {
        $this->username = $data['username'] ?? '';
        $this->participants = $data['participants'] ?? [];
    }
}