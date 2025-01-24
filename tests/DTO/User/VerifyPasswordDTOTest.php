<?php

namespace App\Tests\DTO\User;

use App\DTO\User\VerifyPasswordDTO;
use PHPUnit\Framework\TestCase;

class VerifyPasswordDTOTest extends TestCase
{
    public function testVerifyPasswordDTOInitialization(): void
    {
        $currentPassword = 'SecurePassword123';

        $dto = new VerifyPasswordDTO($currentPassword);

        $this->assertEquals($currentPassword, $dto->currentPassword, 'The current password should be set correctly.');
    }

    public function testVerifyPasswordDTOWithEmptyPassword(): void
    {
        $dto = new VerifyPasswordDTO('');

        $this->assertEmpty($dto->currentPassword, 'The current password should be empty.');
    }
}