<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\ChallengeTypeEnum;
use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeFrequencyEnum;

class CreateChallengeDto
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ChallengeTypeEnum::class, 'cases'], message: 'Type invalide')]
    public string $type;

    #[Assert\NotBlank]
    #[Assert\DateTime(format: 'Y-m-d\\T H:i:sP')]
    public string $startAt;

    #[Assert\NotBlank]
    #[Assert\DateTime(format: 'Y-m-d\\T H:i:sP')]
    public string $endAt;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ChallengeActionTypeEnum::class, 'cases'], message: 'Action invalide')]
    public string $action;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ChallengeContentTypeEnum::class, 'cases'], message: 'Type de contenu invalide')]
    public string $contentType;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ChallengeFrequencyEnum::class, 'cases'], message: 'Fréquence invalide')]
    public string $frequency;

    #[Assert\Positive]
    public int $targetCount;

    /**
     * @var int[] Liste des IDs de profils invités
     */
    #[Assert\All([
        new Assert\Positive,
    ])]
    public array $inviteeIds = [];
}