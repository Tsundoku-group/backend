<?php

namespace App\DTO\Follower;

use Symfony\Component\Validator\Constraints as Assert;

readonly class FollowProfileDTO
{
    #[Assert\NotBlank(message: 'The profile ID is required.')]
    #[Assert\Positive(message: 'The profile ID must be a positive integer.')]
    public int $profileId;

    #[Assert\NotBlank(message: 'The following ID is required.')]
    #[Assert\Positive(message: 'The following ID must be a positive integer.')]
    public int $followingId;

    public function __construct(array $data, int $profileId)
    {
        $this->profileId = $profileId;
        $this->followingId = (int) ($data['followingId'] ?? 0);
    }
}
