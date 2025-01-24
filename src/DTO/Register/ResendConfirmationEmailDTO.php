<?php

namespace App\DTO\Register;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ResendConfirmationEmailDTO
{
    #[Assert\NotBlank(message: 'The email is required.')]
    #[Assert\Email(message: 'The email is not valid.')]
    public function __construct(
        public string $email,
    ) {
    }
}
