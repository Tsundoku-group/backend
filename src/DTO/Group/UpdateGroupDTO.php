<?php

namespace App\DTO\Group;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateGroupDTO
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $groupId;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public string $name;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Choice(['public', 'private'])]
    public string $visibility;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $profileId;

    public function __construct(int $groupId, string $name, ?string $description, string $visibility, int $profileId)
    {
        $this->groupId = $groupId;
        $this->name = $name;
        $this->description = $description;
        $this->visibility = $visibility;
        $this->profileId = $profileId;
    }
}