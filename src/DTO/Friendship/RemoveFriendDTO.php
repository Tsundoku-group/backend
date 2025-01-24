<?php

namespace App\DTO\Friendship;

use Symfony\Component\Validator\Constraints as Assert;

readonly class RemoveFriendDTO
{
    #[Assert\NotBlank(message: 'The requester ID is required.')]
    #[Assert\Positive(message: 'The requester ID must be a positive integer.')]
    public int $requesterId;

    #[Assert\NotBlank(message: 'The receiver ID is required.')]
    #[Assert\Positive(message: 'The receiver ID must be a positive integer.')]
    public int $receiverId;

    public function __construct(array $data)
    {
        $this->requesterId = (int)($data['requesterId'] ?? 0);
        $this->receiverId = (int)($data['receiverId'] ?? 0);
    }
}