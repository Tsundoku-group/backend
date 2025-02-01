<?php

namespace App\DTO\Group;

use Symfony\Component\Validator\Constraints as Assert;

class CreateGroupDTO
{
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

    public function __construct(string $name, ?string $description, string $visibility, int $profileId)
    {
        $this->name = $name;
        $this->description = $description;
        $this->visibility = $visibility;
        $this->profileId = $profileId;
    }
}