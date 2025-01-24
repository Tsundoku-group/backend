<?php

namespace App\DTO\Profile;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ProfileDTO
{
    #[Assert\NotBlank(message: 'The username is required.')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'The username must be at least 3 characters long.',
        maxMessage: 'The username cannot exceed 50 characters.'
    )]
    public string $username;

    #[Assert\Length(max: 50, maxMessage: 'The first name cannot exceed 50 characters.')]
    public ?string $firstName;

    #[Assert\Length(max: 50, maxMessage: 'The last name cannot exceed 50 characters.')]
    public ?string $lastName;

    #[Assert\Date(message: 'The birthday must be a valid date (Y-m-d).')]
    public ?string $birthday;

    #[Assert\Length(max: 20, maxMessage: 'The phone number cannot exceed 20 characters.')]
    public ?string $phoneNumber;

    #[Assert\Length(max: 500, maxMessage: 'The bio cannot exceed 500 characters.')]
    public ?string $bio;

    public string $type;

    public function __construct(array $data)
    {
        $this->username = $data['username'] ?? '';
        $this->firstName = $data['firstName'] ?? null;
        $this->lastName = $data['lastName'] ?? null;
        $this->birthday = $data['birthday'] ?? null;
        $this->phoneNumber = $data['phoneNumber'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->type = $data['type'] ?? null;
    }
}