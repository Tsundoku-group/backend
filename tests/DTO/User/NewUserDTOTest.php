<?php

namespace App\Tests\DTO\User;

use App\DTO\User\NewUserDTO;
use PHPUnit\Framework\TestCase;

class NewUserDTOTest extends TestCase
{
    public function testNewUserDTOInitialization(): void
    {
        $email = 'test@example.com';
        $password = 'securepassword';

        $dto = new NewUserDTO($email, $password);

        $this->assertSame($email, $dto->email, 'L\'email ne correspond pas.');
        $this->assertSame($password, $dto->password, 'Le mot de passe ne correspond pas.');
    }

    public function testNewUserDTOWithEmptyFields(): void
    {
        $email = '';
        $password = '';

        $dto = new NewUserDTO($email, $password);

        $this->assertEmpty($dto->email, 'L\'email devrait être vide.');
        $this->assertEmpty($dto->password, 'Le mot de passe devrait être vide.');
    }
}
