<?php

namespace App\DTO\Message;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SendMessageDTO
{
    #[Assert\NotBlank(message: 'Message content is required.')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'The message cannot exceed 1000 characters.'
    )]
    public string $content;

    #[Assert\NotBlank(message: 'Message ID is required.')]
    #[Assert\Uuid(message: 'The message ID must be a valid UUID.')]
    public string $uuid;

    #[Assert\NotBlank(message: 'Profile ID is required.')]
    public string $sender_id;

    public function __construct(array $data)
    {
        $this->content = $data['content'];
        $this->uuid = $data['uuid'];
        $this->sender_id = $data['sender_id'];
    }
}
