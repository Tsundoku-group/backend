<?php

namespace Tests\DTO\ResetPassword;

use App\DTO\ResetPassword\ForgotPasswordRequestDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordRequestDTOTest extends TestCase
{
    public function testValidEmail(): void
    {
        $validator = Validation::createValidator();

        $dto = new ForgotPasswordRequestDTO('test@example.com');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for a valid email.');
    }

    public function testInvalidEmail(): void
    {
        $validator = Validation::createValidator();

        $dto = new ForgotPasswordRequestDTO('invalid-email');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email], $constraints);

        $this->assertCount(1, $violations, 'There should be one validation error for an invalid email.');
        $this->assertSame('The email is not valid.', $violations[0]->getMessage());
    }
}