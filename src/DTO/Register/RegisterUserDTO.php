<?php

namespace App\DTO\Register;

use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterUserDTO
{
    #[Assert\NotBlank(message: 'The email is required.')]
    #[Assert\Email(message: 'The email is not valid.')]
    public function __construct(
        public string $email,

        #[Assert\NotBlank(message: 'The password is required.')]
        #[Assert\Length(
            min: 8,
            minMessage: 'The password must be at least 8 characters long.'
        )]
        public string $password,
    ) {
    }
}
