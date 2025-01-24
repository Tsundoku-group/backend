<?php

namespace App\DTO\Follower;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UnfollowProfileDTO
{
    #[Assert\NotBlank(message: 'The follower ID is required.')]
    #[Assert\Positive(message: 'The follower ID must be a positive integer.')]
    public int $followerId;

    #[Assert\NotBlank(message: 'The following ID is required.')]
    #[Assert\Positive(message: 'The following ID must be a positive integer.')]
    public int $followingId;

    public function __construct(array $data)
    {
        $this->followerId = (int) ($data['followerId'] ?? 0);
        $this->followingId = (int) ($data['followingId'] ?? 0);
    }
}
