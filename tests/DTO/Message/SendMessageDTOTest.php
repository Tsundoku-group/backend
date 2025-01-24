<?php

namespace App\Tests\DTO\Message;

use App\DTO\Message\SendMessageDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class SendMessageDTOTest extends TestCase
{
    public function testValidSendMessageDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'userEmail' => 'test@example.com',
            'message' => 'This is a test message.',
            'id' => '123e4567-e89b-12d3-a456-426614174000',
        ];
        $dto = new SendMessageDTO($data);

        $constraints = new Assert\Collection([
            'userEmail' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'Invalid email format.'),
            ],
            'message' => [
                new Assert\NotBlank(message: 'Message content is required.'),
                new Assert\Length(
                    max: 1000,
                    maxMessage: 'The message cannot exceed 1000 characters.'
                ),
            ],
            'id' => [
                new Assert\NotBlank(message: 'Message ID is required.'),
                new Assert\Uuid(message: 'The message ID must be a valid UUID.'),
            ],
        ]);

        $violations = $validator->validate([
            'userEmail' => $dto->userEmail,
            'message' => $dto->message,
            'id' => $dto->id,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidSendMessageDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'userEmail' => 'invalid-email',
            'message' => str_repeat('a', 1001),
            'id' => 'invalid-uuid',
        ];
        $dto = new SendMessageDTO($data);

        $constraints = new Assert\Collection([
            'userEmail' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'Invalid email format.'),
            ],
            'message' => [
                new Assert\NotBlank(message: 'Message content is required.'),
                new Assert\Length(
                    max: 1000,
                    maxMessage: 'The message cannot exceed 1000 characters.'
                ),
            ],
            'id' => [
                new Assert\NotBlank(message: 'Message ID is required.'),
                new Assert\Uuid(message: 'The message ID must be a valid UUID.'),
            ],
        ]);

        $violations = $validator->validate([
            'userEmail' => $dto->userEmail,
            'message' => $dto->message,
            'id' => $dto->id,
        ], $constraints);

        $this->assertCount(3, $violations, 'There should be 3 validation errors for invalid input.');

        $expectedErrors = [
            'Invalid email format.',
            'The message cannot exceed 1000 characters.',
            'The message ID must be a valid UUID.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}