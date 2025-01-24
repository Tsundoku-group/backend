<?php

namespace App\Tests\DTO\User;

use App\DTO\User\UpdateUserDTO;
use PHPUnit\Framework\TestCase;

class UpdateUserDTOTest extends TestCase
{
    public function testUpdateUserDTOInitialization(): void
    {
        $email = 'test@example.com';
        $password = 'SecurePassword123';

        $dto = new UpdateUserDTO($email, $password);

        $this->assertEquals($email, $dto->email, 'The email should be set correctly.');
        $this->assertEquals($password, $dto->password, 'The password should be set correctly.');
    }

    public function testUpdateUserDTOWithNullValues(): void
    {
        $dto = new UpdateUserDTO(null, null);

        $this->assertNull($dto->email, 'The email should be null.');
        $this->assertNull($dto->password, 'The password should be null.');
    }
}