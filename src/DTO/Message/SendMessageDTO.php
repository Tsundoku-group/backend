<?php

namespace App\DTO\Message;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SendMessageDTO
{
    #[Assert\NotBlank(message: 'User email is required.')]
    #[Assert\Email(message: 'Invalid email format.')]
    public string $userEmail;

    #[Assert\NotBlank(message: 'Message content is required.')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'The message cannot exceed 1000 characters.'
    )]
    public string $message;

    #[Assert\NotBlank(message: 'Message ID is required.')]
    #[Assert\Uuid(message: 'The message ID must be a valid UUID.')]
    public string $id;

    public function __construct(array $data)
    {
        $this->userEmail = $data['userEmail'] ?? '';
        $this->message = $data['message'] ?? '';
        $this->id = $data['id'] ?? '';
    }
}
