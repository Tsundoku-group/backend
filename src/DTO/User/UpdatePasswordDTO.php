<?php

namespace App\DTO\User;

readonly class UpdatePasswordDTO
{
    public function __construct(
        public string $newPassword,
        public string $captchaToken
    )
    {
    }
}