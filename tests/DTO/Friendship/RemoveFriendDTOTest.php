<?php

namespace App\Tests\DTO\Friendship;

use App\DTO\Friendship\RemoveFriendDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class RemoveFriendDTOTest extends TestCase
{
    public function testValidRemoveFriendDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['requesterId' => 1, 'receiverId' => 2];
        $dto = new RemoveFriendDTO($data);

        $constraints = new Assert\Collection([
            'requesterId' => [
                new Assert\NotBlank(message: 'The requester ID is required.'),
                new Assert\Positive(message: 'The requester ID must be a positive integer.'),
            ],
            'receiverId' => [
                new Assert\NotBlank(message: 'The receiver ID is required.'),
                new Assert\Positive(message: 'The receiver ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'requesterId' => $dto->requesterId,
            'receiverId' => $dto->receiverId,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidRemoveFriendDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['requesterId' => 0, 'receiverId' => -1];
        $dto = new RemoveFriendDTO($data);

        $constraints = new Assert\Collection([
            'requesterId' => [
                new Assert\NotBlank(message: 'The requester ID is required.'),
                new Assert\Positive(message: 'The requester ID must be a positive integer.'),
            ],
            'receiverId' => [
                new Assert\NotBlank(message: 'The receiver ID is required.'),
                new Assert\Positive(message: 'The receiver ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'requesterId' => $dto->requesterId,
            'receiverId' => $dto->receiverId,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'The requester ID must be a positive integer.',
            'The receiver ID must be a positive integer.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}