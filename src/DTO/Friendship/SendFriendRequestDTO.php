<?php

namespace App\DTO\Friendship;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SendFriendRequestDTO
{
    #[Assert\NotBlank(message: 'The profile ID is required.')]
    #[Assert\Positive(message: 'The profile ID must be a positive integer.')]
    public int $profileId;

    #[Assert\NotBlank(message: 'The friend ID is required.')]
    #[Assert\Positive(message: 'The friend ID must be a positive integer.')]
    public int $friendId;

    public function __construct(array $data, int $profileId)
    {
        $this->profileId = $profileId;
        $this->friendId = (int)($data['friendId'] ?? 0);
    }
}