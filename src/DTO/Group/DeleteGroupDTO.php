<?php

namespace App\DTO\Group;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteGroupDTO
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $groupId;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $profileId;

    public function __construct(int $groupId, int $profileId)
    {
        $this->groupId = $groupId;
        $this->profileId = $profileId;
    }
}
