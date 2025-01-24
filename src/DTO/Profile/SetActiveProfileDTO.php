<?php

namespace App\DTO\Profile;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SetActiveProfileDTO
{
    #[Assert\NotBlank(message: 'The user ID is required.')]
    #[Assert\Positive(message: 'The user ID must be a positive integer.')]
    public int $userId;

    #[Assert\NotBlank(message: 'The profile ID is required.')]
    #[Assert\Positive(message: 'The profile ID must be a positive integer.')]
    public int $profileId;

    public function __construct(array $data)
    {
        $this->userId = (int) ($data['id'] ?? 0);
        $this->profileId = (int) ($data['profileId'] ?? 0);
    }
}
