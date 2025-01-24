<?php

namespace App\Tests\DTO\Register;

use App\DTO\Register\RegisterUserDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserDTOTest extends TestCase
{
    public function testValidRegisterUserDTO(): void
    {
        $validator = Validation::createValidator();

        $dto = new RegisterUserDTO('test@example.com', 'StrongPassword123!');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
            'password' => [
                new Assert\NotBlank(message: 'The password is required.'),
                new Assert\Length(
                    min: 8,
                    minMessage: 'The password must be at least 8 characters long.'
                ),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email, 'password' => $dto->password], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidRegisterUserDTO(): void
    {
        $validator = Validation::createValidator();

        $dto = new RegisterUserDTO('', 'short');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
            'password' => [
                new Assert\NotBlank(message: 'The password is required.'),
                new Assert\Length(
                    min: 8,
                    minMessage: 'The password must be at least 8 characters long.'
                ),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email, 'password' => $dto->password], $constraints);

        $this->assertCount(2, $violations, 'There should be two validation errors for invalid input.');
        $this->assertSame('The email is required.', $violations[0]->getMessage());
        $this->assertSame('The password must be at least 8 characters long.', $violations[1]->getMessage());
    }
}