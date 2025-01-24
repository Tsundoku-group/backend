<?php

namespace App\DTO\ResetPassword;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ResetPasswordRequestDTO
{
    #[Assert\NotBlank(message: 'The token is required.')]
    public function __construct(
        public string $token,

        #[Assert\NotBlank(message: 'The password is required.')]
        #[Assert\Length(
            min: 8,
            minMessage: 'The password must be at least 8 characters long.'
        )]
        public string $password,
    ) {
    }
}
