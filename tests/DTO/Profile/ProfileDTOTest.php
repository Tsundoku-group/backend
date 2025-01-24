<?php

namespace App\Tests\DTO\Profile;

use App\DTO\Profile\ProfileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileDTOTest extends TestCase
{
    public function testValidProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'username' => 'ValidUsername',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthday' => '1990-01-01',
            'phoneNumber' => '1234567890',
            'bio' => 'This is a valid bio.',
            'type' => 'reader',
        ];

        $dto = new ProfileDTO($data);

        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(message: 'The username is required.'),
                new Assert\Length(
                    min: 3,
                    max: 50,
                    minMessage: 'The username must be at least 3 characters long.',
                    maxMessage: 'The username cannot exceed 50 characters.'
                ),
            ],
            'firstName' => [
                new Assert\Length(
                    max: 50,
                    maxMessage: 'The first name cannot exceed 50 characters.'
                ),
            ],
            'lastName' => [
                new Assert\Length(
                    max: 50,
                    maxMessage: 'The last name cannot exceed 50 characters.'
                ),
            ],
            'birthday' => [
                new Assert\Date(message: 'The birthday must be a valid date (Y-m-d).'),
            ],
            'phoneNumber' => [
                new Assert\Length(
                    max: 20,
                    maxMessage: 'The phone number cannot exceed 20 characters.'
                ),
            ],
            'bio' => [
                new Assert\Length(
                    max: 500,
                    maxMessage: 'The bio cannot exceed 500 characters.'
                ),
            ],
            'type' => [
                new Assert\NotBlank(message: 'The type is required.'),
            ],
        ]);

        $violations = $validator->validate([
            'username' => $dto->username,
            'firstName' => $dto->firstName,
            'lastName' => $dto->lastName,
            'birthday' => $dto->birthday,
            'phoneNumber' => $dto->phoneNumber,
            'bio' => $dto->bio,
            'type' => $dto->type,
        ], $constraints);

        $this->assertCount(0, $violations);
    }

    public function testInvalidProfileDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'username' => 'U',
            'firstName' => str_repeat('A', 51),
            'lastName' => str_repeat('B', 51),
            'birthday' => 'invalid-date',
            'phoneNumber' => str_repeat('1', 21),
            'bio' => str_repeat('C', 501),
            'type' => '',
        ];

        $dto = new ProfileDTO($data);

        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(message: 'The username is required.'),
                new Assert\Length(
                    min: 3,
                    max: 50,
                    minMessage: 'The username must be at least 3 characters long.',
                    maxMessage: 'The username cannot exceed 50 characters.'
                ),
            ],
            'firstName' => [
                new Assert\Length(
                    max: 50,
                    maxMessage: 'The first name cannot exceed 50 characters.'
                ),
            ],
            'lastName' => [
                new Assert\Length(
                    max: 50,
                    maxMessage: 'The last name cannot exceed 50 characters.'
                ),
            ],
            'birthday' => [
                new Assert\Date(message: 'The birthday must be a valid date (Y-m-d).'),
            ],
            'phoneNumber' => [
                new Assert\Length(
                    max: 20,
                    maxMessage: 'The phone number cannot exceed 20 characters.'
                ),
            ],
            'bio' => [
                new Assert\Length(
                    max: 500,
                    maxMessage: 'The bio cannot exceed 500 characters.'
                ),
            ],
            'type' => [
                new Assert\NotBlank(message: 'The type is required.'),
            ],
        ]);

        $violations = $validator->validate([
            'username' => $dto->username,
            'firstName' => $dto->firstName,
            'lastName' => $dto->lastName,
            'birthday' => $dto->birthday,
            'phoneNumber' => $dto->phoneNumber,
            'bio' => $dto->bio,
            'type' => $dto->type,
        ], $constraints);

        $this->assertGreaterThan(0, count($violations));

        $expectedErrors = [
            'The username must be at least 3 characters long.',
            'The first name cannot exceed 50 characters.',
            'The last name cannot exceed 50 characters.',
            'The birthday must be a valid date (Y-m-d).',
            'The phone number cannot exceed 20 characters.',
            'The bio cannot exceed 500 characters.',
            'The type is required.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}