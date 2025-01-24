<?php

namespace App\DTO\User;

readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $email,
        public ?string $password,
    ) {
    }
}
