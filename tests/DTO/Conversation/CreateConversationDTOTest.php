<?php

namespace App\Tests\DTO\Conversation;

use App\DTO\Conversation\CreateConversationDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class CreateConversationDTOTest extends TestCase
{
    public function testValidCreateConversationDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'username' => 'test@example.com',
            'participants' => [1, 2, 3],
        ];
        $dto = new CreateConversationDTO($data);

        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'The email must be valid.'),
            ],
            'participants' => [
                new Assert\NotBlank(message: 'Participants are required.'),
                new Assert\All([
                    new Assert\Type(type: 'integer', message: 'Participant IDs must be integers.'),
                    new Assert\Positive(message: 'Participant IDs must be positive.'),
                ]),
            ],
        ]);

        $violations = $validator->validate([
            'username' => $dto->username,
            'participants' => $dto->participants,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidCreateConversationDTO(): void
    {
        $validator = Validation::createValidator();

        $data = [
            'username' => 'invalid-email',
            'participants' => [1, -2, 'three'],
        ];
        $dto = new CreateConversationDTO($data);

        $constraints = new Assert\Collection([
            'username' => [
                new Assert\NotBlank(message: 'User email is required.'),
                new Assert\Email(message: 'The email must be valid.'),
            ],
            'participants' => [
                new Assert\NotBlank(message: 'Participants are required.'),
                new Assert\All([
                    new Assert\Type(type: 'integer', message: 'Participant IDs must be integers.'),
                    new Assert\Positive(message: 'Participant IDs must be positive.'),
                ]),
            ],
        ]);

        $violations = $validator->validate([
            'username' => $dto->username,
            'participants' => $dto->participants,
        ], $constraints);

        $this->assertCount(3, $violations, 'There should be 3 validation errors for invalid input.');

        $expectedErrors = [
            'The email must be valid.',
            'Participant IDs must be positive.',
            'Participant IDs must be integers.',
        ];

        $actualErrors = array_map(fn($violation) => $violation->getMessage(), iterator_to_array($violations));

        foreach ($expectedErrors as $expectedError) {
            $this->assertContains($expectedError, $actualErrors);
        }
    }
}