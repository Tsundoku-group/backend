<?php

namespace App\Tests\DTO\Message;

use App\DTO\Message\GetMessageDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class GetMessageDTOTest extends TestCase
{
    public function testValidGetMessageDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['page' => 1, 'limit' => 20];
        $dto = new GetMessageDTO($data);

        $constraints = new Assert\Collection([
            'page' => [
                new Assert\PositiveOrZero(message: 'Page must be a positive number or zero.'),
            ],
            'limit' => [
                new Assert\Positive(message: 'Limit must be a positive number.'),
            ],
        ]);

        $violations = $validator->validate([
            'page' => $dto->page,
            'limit' => $dto->limit,
        ], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidGetMessageDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['page' => -1, 'limit' => 0];
        $dto = new GetMessageDTO($data);

        $constraints = new Assert\Collection([
            'page' => [
                new Assert\PositiveOrZero(message: 'Page must be a positive number or zero.'),
            ],
            'limit' => [
                new Assert\Positive(message: 'Limit must be a positive number.'),
            ],
        ]);

        $violations = $validator->validate([
            'page' => $dto->page,
            'limit' => $dto->limit,
        ], $constraints);

        $this->assertCount(2, $violations, 'There should be 2 validation errors for invalid input.');

        $expectedErrors = [
            'Page must be a positive number or zero.',
            'Limit must be a positive number.',
        ];

        foreach ($violations as $index => $violation) {
            $this->assertSame($expectedErrors[$index], $violation->getMessage());
        }
    }
}