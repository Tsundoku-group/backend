<?php

namespace App\Tests\DTO\Friendship;

use App\DTO\Friendship\SendFriendRequestDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class SendFriendRequestDTOTest extends TestCase
{
    public function testValidSendFriendRequestDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['friendId' => 2];
        $profileId = 1;
        $dto = new SendFriendRequestDTO($data, $profileId);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'friendId' => [
                new Assert\NotBlank(message: 'The friend ID is required.'),
                new Assert\Positive(message: 'The friend ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'friendId' => $dto->friendId,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidSendFriendRequestDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['friendId' => 0];
        $profileId = -1;
        $dto = new SendFriendRequestDTO($data, $profileId);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'friendId' => [
                new Assert\NotBlank(message: 'The friend ID is required.'),
                new Assert\Positive(message: 'The friend ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'friendId' => $dto->friendId,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'The profile ID must be a positive integer.',
            'The friend ID must be a positive integer.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}