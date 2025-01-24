<?php

namespace App\Tests\DTO\User;

use App\DTO\User\UpdatePasswordDTO;
use PHPUnit\Framework\TestCase;

class UpdatePasswordDTOTest extends TestCase
{
    public function testUpdatePasswordDTOInitialization(): void
    {
        $newPassword = 'SecurePassword123!';
        $captchaToken = 'captcha-token-example';

        $dto = new UpdatePasswordDTO($newPassword, $captchaToken);

        $this->assertEquals($newPassword, $dto->newPassword, 'The new password should be set correctly.');
        $this->assertEquals($captchaToken, $dto->captchaToken, 'The captcha token should be set correctly.');
    }

    public function testUpdatePasswordDTOWithEmptyFields(): void
    {
        $dto = new UpdatePasswordDTO('', '');

        $this->assertEmpty($dto->newPassword, 'The new password should be empty.');
        $this->assertEmpty($dto->captchaToken, 'The captcha token should be empty.');
    }
}