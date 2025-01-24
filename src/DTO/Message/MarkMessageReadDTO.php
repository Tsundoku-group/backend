<?php

namespace App\DTO\Message;

use Symfony\Component\Validator\Constraints as Assert;

readonly class MarkMessageReadDTO
{
    #[Assert\NotBlank(message: 'User email is required.')]
    #[Assert\Email(message: 'Invalid email format.')]
    public string $userEmail;

    public function __construct(array $data)
    {
        $this->userEmail = $data['userEmail'] ?? '';
    }
}
