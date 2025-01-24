<?php

namespace App\Tests\DTO\Follower;

use App\DTO\Follower\UnfollowProfileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class UnfollowProfileDTOTest extends TestCase
{
    public function testValidUnfollowProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['followerId' => 1, 'followingId' => 2];
        $dto = new UnfollowProfileDTO($data);

        $constraints = new Assert\Collection([
            'followerId' => [
                new Assert\NotBlank(message: 'The follower ID is required.'),
                new Assert\Positive(message: 'The follower ID must be a positive integer.'),
            ],
            'followingId' => [
                new Assert\NotBlank(message: 'The following ID is required.'),
                new Assert\Positive(message: 'The following ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'followerId' => $dto->followerId,
            'followingId' => $dto->followingId,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidUnfollowProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['followerId' => 0, 'followingId' => -1];
        $dto = new UnfollowProfileDTO($data);

        $constraints = new Assert\Collection([
            'followerId' => [
                new Assert\NotBlank(message: 'The follower ID is required.'),
                new Assert\Positive(message: 'The follower ID must be a positive integer.'),
            ],
            'followingId' => [
                new Assert\NotBlank(message: 'The following ID is required.'),
                new Assert\Positive(message: 'The following ID must be a positive integer.'),
            ],
        ]);

        $violations = $validator->validate([
            'followerId' => $dto->followerId,
            'followingId' => $dto->followingId,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'The follower ID must be a positive integer.',
            'The following ID must be a positive integer.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}