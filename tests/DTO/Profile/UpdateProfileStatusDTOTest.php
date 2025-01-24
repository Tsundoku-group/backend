<?php

namespace App\Tests\DTO\Profile;

use App\DTO\Profile\UpdateProfileStatusDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileStatusDTOTest extends TestCase
{
    public function testValidUpdateProfileStatusDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['status' => 'online'];
        $dto = new UpdateProfileStatusDTO($data);

        $constraints = new Assert\Collection([
            'status' => [
                new Assert\NotBlank(message: 'The status is required.'),
                new Assert\Choice(
                    choices: ['online', 'offline', 'away', 'do_not_disturb'],
                    message: 'The status must be one of the following: online, offline, away, do_not_disturb.'
                ),
            ],
        ]);

        $violations = $validator->validate(['status' => $dto->status], $constraints);

        $this->assertCount(0, $violations, 'There should be no validation errors for valid input.');
    }

    public function testInvalidUpdateProfileStatusDTO(): void
    {
        $validator = Validation::createValidator();

        $data = ['status' => 'invalid_status'];
        $dto = new UpdateProfileStatusDTO($data);

        $constraints = new Assert\Collection([
            'status' => [
                new Assert\NotBlank(message: 'The status is required.'),
                new Assert\Choice(
                    choices: ['online', 'offline', 'away', 'do_not_disturb'],
                    message: 'The status must be one of the following: online, offline, away, do_not_disturb.'
                ),
            ],
        ]);

        $violations = $validator->validate(['status' => $dto->status], $constraints);

        $this->assertCount(1, $violations, 'There should be 1 validation error for invalid input.');

        $expectedError = 'The status must be one of the following: online, offline, away, do_not_disturb.';

        $this->assertSame($expectedError, $violations[0]->getMessage());
    }
}