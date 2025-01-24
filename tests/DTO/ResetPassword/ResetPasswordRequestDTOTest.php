<?php

namespace Tests\DTO\ResetPassword;

use App\DTO\ResetPassword\ResetPasswordRequestDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequestDTOTest extends TestCase
{
    public function testValidResetPasswordRequest(): void
    {
        $validator = Validation::createValidator();

        $dto = new ResetPasswordRequestDTO('valid-token', 'StrongPassword123!');
        $constraints = new Assert\Collection([
            'token' => [
                new Assert\NotBlank(message: 'The token is required.'),
            ],
            'password' => [
                new Assert\NotBlank(message: 'The password is required.'),
                new Assert\Length(
                    min: 8,
                    minMessage: 'The password must be at least 8 characters long.'
                ),
            ],
        ]);

        $violations = $validator->validate(['token' => $dto->token, 'password' => $dto->password], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidResetPasswordRequest(): void
    {
        $validator = Validation::createValidator();

        $dto = new ResetPasswordRequestDTO('', 'short');
        $constraints = new Assert\Collection([
            'token' => [
                new Assert\NotBlank(message: 'The token is required.'),
            ],
            'password' => [
                new Assert\NotBlank(message: 'The password is required.'),
                new Assert\Length(
                    min: 8,
                    minMessage: 'The password must be at least 8 characters long.'
                ),
            ],
        ]);

        $violations = $validator->validate(['token' => $dto->token, 'password' => $dto->password], $constraints);

        $this->assertCount(2, $violations, 'There should be two validation errors for invalid input.');
        $this->assertSame('The token is required.', $violations[0]->getMessage());
        $this->assertSame('The password must be at least 8 characters long.', $violations[1]->getMessage());
    }
}