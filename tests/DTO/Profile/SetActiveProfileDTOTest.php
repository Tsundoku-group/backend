<?php

namespace App\Tests\DTO\Profile;

use App\DTO\Profile\SetActiveProfileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class SetActiveProfileDTOTest extends TestCase
{
    public function testValidSetActiveProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'id' => 1,
            'profileId' => 2,
        ];
        $dto = new SetActiveProfileDTO($data);

        $constraints = new Assert\Collection([
            'userId' => [
                new Assert\NotBlank(message: 'The user ID is required.'),
                new Assert\Positive(message: 'The user ID must be a positive integer.'),
            ],
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'userId' => $dto->userId,
            'profileId' => $dto->profileId,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidSetActiveProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'id' => 0,
            'profileId' => -1,
        ];
        $dto = new SetActiveProfileDTO($data);

        $constraints = new Assert\Collection([
            'userId' => [
                new Assert\NotBlank(message: 'The user ID is required.'),
                new Assert\Positive(message: 'The user ID must be a positive integer.'),
            ],
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'userId' => $dto->userId,
            'profileId' => $dto->profileId,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'The user ID must be a positive integer.',
            'The profile ID must be a positive integer.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}