<?php

namespace App\Tests\DTO\Register;

use App\DTO\Register\ResendConfirmationEmailDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ResendConfirmationEmailDTOTest extends TestCase
{
    public function testValidResendConfirmationEmailDTO(): void
    {
        $validator = Validation::createValidator();

        $dto = new ResendConfirmationEmailDTO('test@example.com');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidResendConfirmationEmailDTO(): void
    {
        $validator = Validation::createValidator();

        $dto = new ResendConfirmationEmailDTO('');
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(message: 'The email is required.'),
                new Assert\Email(message: 'The email is not valid.'),
            ],
        ]);

        $violations = $validator->validate(['email' => $dto->email], $constraints);

        $this->assertCount(1, $violations, 'There should be one validation error for invalid input.');
        $this->assertSame('The email is required.', $violations[0]->getMessage());
    }
}