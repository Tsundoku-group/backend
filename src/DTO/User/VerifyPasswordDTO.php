<?php

namespace App\DTO\User;

readonly class VerifyPasswordDTO
{
    public function __construct(
        public string $currentPassword,
    ) {
    }
}
