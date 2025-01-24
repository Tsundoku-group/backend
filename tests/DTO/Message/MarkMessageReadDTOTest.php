<?php

namespace App\Tests\DTO\Message;

use App\DTO\Message\MarkMessageReadDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class MarkMessageReadDTOTest extends TestCase
{
    public function testValidMarkMessageReadDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['userEmail' => 'test@example.com'];
        $dto = new MarkMessageReadDTO($data);

        $constraints = new Assert\Collection([
            'userEmail' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'Invalid email format.'),
            ],
        ]);

        $violations = $validator->validate(['userEmail' => $dto->userEmail], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidMarkMessageReadDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['userEmail' => 'invalid-email'];
        $dto = new MarkMessageReadDTO($data);

        $constraints = new Assert\Collection([
            'userEmail' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'Invalid email format.'),
            ],
        ]);

        $violations = $validator->validate(['userEmail' => $dto->userEmail], $constraints);

        $this->assertCount(1, $violations, 'There should be 1 validation error for invalid input.');
        $this->assertSame('Invalid email format.', $violations[0]->getMessage());
    }
}