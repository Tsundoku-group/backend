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
            'content' => 'This is a test message.',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'sender_id' => '1',
        ];
        $dto = new SendMessageDTO($data);

        $constraints = new Assert\Collection([
            'content' => [
                new Assert\NotBlank(message: 'Message content is required.'),
                new Assert\Length(
                    max: 1000,
                    maxMessage: 'The message cannot exceed 1000 characters.'
                ),
            ],
            'uuid' => [
                new Assert\NotBlank(message: 'Message ID is required.'),
                new Assert\Uuid(message: 'The message ID must be a valid UUID.'),
            ],
            'sender_id' => [
                new Assert\NotBlank(message: 'Profile ID is required.'),
            ],
        ]);

        $violations = $validator->validate([
            'content' => $dto->content,
            'uuid' => $dto->uuid,
            'sender_id' => $dto->sender_id,
        ], $constraints);

        $this->assertCount(0, $violations);
    }

    public function testInvalidSendMessageDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'content' => str_repeat('a', 1001),
            'uuid' => 'invalid-uuid',
            'sender_id' => '',
        ];
        $dto = new SendMessageDTO($data);

        $constraints = new Assert\Collection([
            'content' => [
                new Assert\NotBlank(message: 'Message content is required.'),
                new Assert\Length(
                    max: 1000,
                    maxMessage: 'The message cannot exceed 1000 characters.'
                ),
            ],
            'uuid' => [
                new Assert\NotBlank(message: 'Message ID is required.'),
                new Assert\Uuid(message: 'The message ID must be a valid UUID.'),
            ],
            'sender_id' => [
                new Assert\NotBlank(message: 'Profile ID is required.'),
            ],
        ]);

        $violations = $validator->validate([
            'content' => $dto->content,
            'uuid' => $dto->uuid,
            'sender_id' => $dto->sender_id,
        ], $constraints);

        $this->assertCount(3, $violations);

        $expectedErrors = [
            'The message cannot exceed 1000 characters.',
            'The message ID must be a valid UUID.',
            'Profile ID is required.',
        ];

        $actualErrors = array_map(fn($v) => $v->getMessage(), iterator_to_array($violations));

        $this->assertEqualsCanonicalizing($expectedErrors, $actualErrors);
    }
}