<?php

namespace App\Tests\DTO\Follower;

use App\DTO\Follower\FollowProfileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class FollowProfileDTOTest extends TestCase
{
    public function testValidFollowProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['followingId' => 2];
        $profileId = 1;
        $dto = new FollowProfileDTO($data, $profileId);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'followingId' => [
                new Assert\NotBlank(message: 'The following ID is required.'),
                new Assert\Positive(message: 'The following ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'followingId' => $dto->followingId,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidFollowProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['followingId' => -2];
        $profileId = 0;
        $dto = new FollowProfileDTO($data, $profileId);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'followingId' => [
                new Assert\NotBlank(message: 'The following ID is required.'),
                new Assert\Positive(message: 'The following ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'followingId' => $dto->followingId,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'The profile ID must be a positive integer.',
            'The following ID must be a positive integer.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}