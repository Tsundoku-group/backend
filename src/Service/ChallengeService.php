<?php

namespace App\Service;

use App\Dto\CreateChallengeDto;
use App\Entity\Challenge;
use App\Entity\ChallengeProfile;
use App\Entity\Profile;
use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeFrequencyEnum;
use App\Enum\ChallengeStatusEnum;
use App\Enum\ChallengeTypeEnum;
use App\ValueObject\ChallengeConstraint;
use Doctrine\ORM\EntityManagerInterface;

class ChallengeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function createChallenge(CreateChallengeDto $dto, Profile $creator): Challenge
    {
        $typeEnum    = ChallengeTypeEnum::from($dto->type);
        $actionEnum  = ChallengeActionTypeEnum::from($dto->action);
        $contentEnum = ChallengeContentTypeEnum::from($dto->contentType);
        $freqEnum    = ChallengeFrequencyEnum::from($dto->frequency);

        $constraint = new ChallengeConstraint(
            $actionEnum,
            $contentEnum,
            $freqEnum,
            $dto->targetCount
        );

        $challenge = new Challenge($creator);
        $challenge
            ->setName($dto->name)
            ->setType($typeEnum)
            ->setStatus(ChallengeStatusEnum::PENDING)
            ->setStartAt($this->parseDateTime($dto->startAt))
            ->setEndAt($this->parseDateTime($dto->endAt))
            ->setConstraint($constraint);

        $creatorChallengeProfile = new ChallengeProfile($challenge, $creator, 'admin');
        $creatorChallengeProfile->setProgress(0);

        $this->em->persist($challenge);
        $this->em->persist($creatorChallengeProfile);
        $this->em->flush();

        return $challenge;
    }

    private function parseDateTime(string $dateString): \DateTimeImmutable
    {
        $timezone = new \DateTimeZone('Europe/Paris');

        $dateString = trim($dateString);

        if (preg_match('/\+\d{2}:\d{2}$/', $dateString)) {
            return new \DateTimeImmutable($dateString);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dateString)) {
            $dateString .= ':00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $dateString)) {
            return new \DateTimeImmutable($dateString, $timezone);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            $dateString .= 'T00:00:00';
            return new \DateTimeImmutable($dateString, $timezone);
        }

        try {
            return new \DateTimeImmutable($dateString, $timezone);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$dateString}");
        }
    }

    public function addParticipant(Challenge $challenge, Profile $participant): ChallengeProfile
    {
        $existingParticipation = $this->em->getRepository(ChallengeProfile::class)
            ->findOneBy([
                'challenge' => $challenge,
                'profile' => $participant
            ]);

        if ($existingParticipation) {
            throw new \InvalidArgumentException('This profile is already part of this challenge.');
        }

        $challengeProfile = new ChallengeProfile($challenge, $participant, 'participant');
        $challengeProfile->setProgress(0);

        $this->em->persist($challengeProfile);
        $this->em->flush();

        return $challengeProfile;
    }

    public function updateProgress(ChallengeProfile $challengeProfile, int $progress): ChallengeProfile
    {
        $challengeProfile->setProgress($progress);

        $this->em->persist($challengeProfile);
        $this->em->flush();

        return $challengeProfile;
    }

    public function getChallengeParticipants(Challenge $challenge): array
    {
        return $this->em->getRepository(ChallengeProfile::class)
            ->findBy(['challenge' => $challenge]);
    }

    public function getProfileChallenges(Profile $profile): array
    {
        return $this->em->getRepository(ChallengeProfile::class)
            ->findBy(['profile' => $profile]);
    }

    public function getChallengeConstraints(): array
    {
        $actionTypes  = array_map(fn(ChallengeActionTypeEnum $e) => $e->value, ChallengeActionTypeEnum::cases());
        $contentTypes = array_map(fn(ChallengeContentTypeEnum $e) => $e->value, ChallengeContentTypeEnum::cases());
        $frequencies  = array_map(fn(ChallengeFrequencyEnum $e) => $e->value, ChallengeFrequencyEnum::cases());

        $allowedContent = [
            ChallengeActionTypeEnum::WRITE->value => [
                ChallengeContentTypeEnum::ARTICLE->value,
                ChallengeContentTypeEnum::BOOK_REVIEW->value,
                ChallengeContentTypeEnum::BOOK_DESCRIPTION->value,
            ],
            ChallengeActionTypeEnum::READ->value => [
                ChallengeContentTypeEnum::BOOK->value,
                ChallengeContentTypeEnum::PAGE->value,
                ChallengeContentTypeEnum::CHAPTER->value,
            ],
            ChallengeActionTypeEnum::HAVE->value => [
                ChallengeContentTypeEnum::BOOK->value,
            ],
        ];

        return [
            'actionTypes'    => $actionTypes,
            'contentTypes'   => $contentTypes,
            'frequencies'    => $frequencies,
            'allowedContent' => $allowedContent,
        ];
    }

    public function deleteChallenge(Challenge $challenge): void
    {
        $this->em->remove($challenge);
        $this->em->flush();
    }
}
