<?php

namespace App\DTO\Profile;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateProfileStatusDTO
{
    #[Assert\NotBlank(message: 'The status is required.')]
    #[Assert\Choice(
        choices: ['online', 'offline', 'away', 'do_not_disturb'],
        message: 'The status must be one of the following: online, offline, away, do_not_disturb.'
    )]
    public string $status;

    public function __construct(array $data)
    {
        $this->status = $data['status'] ?? '';
    }
}
