<?php

namespace App\DTO\User;

readonly class NewUserDTO
{
    public function __construct(
        public string $email,
        public string $password
    )
    {
    }
}