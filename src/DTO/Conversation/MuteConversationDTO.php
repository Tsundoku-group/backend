<?php

namespace App\DTO\Conversation;

use Symfony\Component\Validator\Constraints as Assert;

readonly class MuteConversationDTO
{
    #[Assert\NotBlank(message: 'Mute duration is required.')]
    #[Assert\Type(type: 'string', message: 'Duration must be a string.')]
    public string $duration;

    public function __construct(array $data)
    {
        $this->duration = $data['duration'] ?? '';
    }
}