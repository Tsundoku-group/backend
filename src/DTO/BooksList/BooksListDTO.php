<?php

namespace App\DTO\BooksList;

use App\Enum\VisibilityEnum;
use Symfony\Component\Validator\Constraints as Assert;

class BooksListDTO
{
    #[Assert\NotBlank(message: 'profile is required')]
    public int $profile;

    #[Assert\NotBlank(message: 'title is required')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'title must be at least 3 characters', maxMessage: 'title must be at most 255 characters')]
    public string $title;

    #[Assert\NotBlank(message: 'visibility is required')]
    public VisibilityEnum $visibility;

    public bool $favorite;
}
