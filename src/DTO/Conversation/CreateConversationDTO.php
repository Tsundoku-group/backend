<?php

namespace App\DTO\Conversation;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateConversationDTO
{
    #[Assert\NotBlank(message: 'User email is required.')]
    #[Assert\Email(message: 'The email must be valid.')]
    public string $email;

    #[Assert\NotBlank(message: 'Participants are required.')]
    #[Assert\All([
        new Assert\Type(type: 'integer', message: 'Participant IDs must be integers.'),
        new Assert\Positive(message: 'Participant IDs must be positive.'),
    ])]
    public array $participants;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->participants = $data['participants'] ?? [];
    }
}
