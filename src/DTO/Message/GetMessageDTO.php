<?php

namespace App\DTO\Message;

use Symfony\Component\Validator\Constraints as Assert;

readonly class GetMessageDTO
{
    #[Assert\PositiveOrZero(message: 'Page must be a positive number or zero.')]
    public int $page;

    #[Assert\Positive(message: 'Limit must be a positive number.')]
    public int $limit;

    public function __construct(array $queryParams)
    {
        $this->page = (int)($queryParams['page'] ?? 1);
        $this->limit = (int)($queryParams['limit'] ?? 20);
    }
}