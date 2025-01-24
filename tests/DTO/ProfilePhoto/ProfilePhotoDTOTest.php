<?php

namespace App\Tests\DTO\ProfilePhoto;

use App\DTO\ProfilePhoto\ProfilePhotoDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ProfilePhotoDTOTest extends TestCase
{
    public function testValidProfilePhotoDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'profileId' => 1,
            'id' => 2,
            'url' => 'https://example.com/photo.jpg',
            'type' => 'profile',
        ];
        $dto = new ProfilePhotoDTO($data);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'userId' => [
                new Assert\NotBlank(message: 'The user ID is required.'),
                new Assert\Positive(message: 'The user ID must be a positive integer.'),
            ],
            'url' => [
                new Assert\NotBlank(message: 'The photo URL is required.'),
                new Assert\Url(
                    requireTld: true,
                    message: 'The photo URL is not valid.'
                ),
            ],
            'type' => [
                new Assert\NotBlank(message: 'The photo type is required.'),
                new Assert\Choice(
                    choices: ['profile', 'cover'],
                    message: 'The photo type must be either "profile" or "cover".'
                ),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'userId' => $dto->userId,
            'url' => $dto->url,
            'type' => $dto->type,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidProfilePhotoDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'profileId' => 0,
            'id' => -1,
            'url' => 'invalid-url',
            'type' => 'invalid-type',
        ];
        $dto = new ProfilePhotoDTO($data);

        $constraints = new Assert\Collection([
            'profileId' => [
                new Assert\NotBlank(message: 'The profile ID is required.'),
                new Assert\Positive(message: 'The profile ID must be a positive integer.'),
            ],
            'userId' => [
                new Assert\NotBlank(message: 'The user ID is required.'),
                new Assert\Positive(message: 'The user ID must be a positive integer.'),
            ],
            'url' => [
                new Assert\NotBlank(message: 'The photo URL is required.'),
                new Assert\Url(
                    requireTld: true,
                    message: 'The photo URL is not valid.'
                ),
            ],
            'type' => [
                new Assert\NotBlank(message: 'The photo type is required.'),
                new Assert\Choice(
                    choices: ['profile', 'cover'],
                    message: 'The photo type must be either "profile" or "cover".'
                ),
            ],
        ]);

        $violations = $validator->validate([
            'profileId' => $dto->profileId,
            'userId' => $dto->userId,
            'url' => $dto->url,
            'type' => $dto->type,
        ], $constraints);

        $this->assertCount(4, $violations, 'There should be 4 validation errors for invalid input.');

        $expectedErrors = [
            'The profile ID must be a positive integer.',
            'The user ID must be a positive integer.',
            'The photo URL is not valid.',
            'The photo type must be either "profile" or "cover".',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}