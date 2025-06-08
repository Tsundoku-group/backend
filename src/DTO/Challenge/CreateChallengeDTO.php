<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateChallengeDto
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Choice(
        choices: ['community', 'predefined', 'customised'],
        message: 'Invalid type, accepted values : community, predefined, customised'
    )]
    public string $type;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(\+\d{2}:\d{2})?$/',
        message: 'Invalid date format. Expected: YYYY-MM-DDTHH:mm, YYYY-MM-DDTHH:mm:ss or with timezone'
    )]
    public string $startAt;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(\+\d{2}:\d{2})?$/',
        message: 'Invalid date format. Expected: YYYY-MM-DDTHH:mm, YYYY-MM-DDTHH:mm:ss or with timezone'
    )]
    public string $endAt;

    #[Assert\NotNull]
    #[Assert\Choice(
        choices: ['read', 'write', 'have'],
        message: 'Invalid action, accepted values: read, write, have'
    )]
    public string $action;

    #[Assert\NotNull]
    #[Assert\Choice(
        choices: ['book', 'page', 'chapter', 'article', 'book_review', 'book_description'],
        message: 'Invalid content type, accepted values: book, page, chapter, article, book_review, book_description'
    )]
    public string $contentType;

    #[Assert\NotNull]
    #[Assert\Choice(
        choices: ['daily', 'weekly', 'monthly', 'yearly', 'once'],
        message: 'Invalid frequency, accepted values: daily, weekly, monthly, yearly, once'
    )]
    public string $frequency;

    #[Assert\Positive]
    public int $targetCount;

    #[Assert\All([
        new Assert\Positive(),
    ])]
    public array $inviteeIds = [];
}