<?php

namespace App\DTO\ProfilePhoto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ProfilePhotoDTO
{
    #[Assert\NotBlank(message: 'The profile ID is required.')]
    #[Assert\Positive(message: 'The profile ID must be a positive integer.')]
    public int $profileId;

    #[Assert\NotBlank(message: 'The user ID is required.')]
    #[Assert\Positive(message: 'The user ID must be a positive integer.')]
    public int $userId;

    #[Assert\NotBlank(message: 'The photo URL is required.')]
    #[Assert\Url(message: 'The photo URL is not valid.')]
    public string $url;

    #[Assert\NotBlank(message: 'The photo type is required.')]
    #[Assert\Choice(
        choices: ['profile', 'cover'],
        message: 'The photo type must be either "profile" or "cover".'
    )]
    public string $type;

    public function __construct(array $data)
    {
        $this->profileId = (int) ($data['profileId'] ?? 0);
        $this->userId = (int) ($data['id'] ?? 0);
        $this->url = $data['url'] ?? '';
        $this->type = $data['type'] ?? '';
    }
}
