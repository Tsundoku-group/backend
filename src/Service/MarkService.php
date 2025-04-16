<?php

namespace App\Service;

use App\Entity\Mark;
use App\Repository\MarkRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class MarkService
{
    private MarkRepository $markRepository;
    private ProfileRepository $profileRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MarkRepository $markRepository,
        ProfileRepository $profileRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->markRepository = $markRepository;
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;
    }

    public function createOrUpdateMark(
        int $profileId,
        int $targetId,
        string $targetType,
        ?float $rating = null,
        bool $isPinned = false,
        bool $isFavorite = false
    ): Mark {
        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            throw new \Exception("Profil non trouvé.");
        }

        $existingMark = $this->markRepository->findOneBy([
            'profile' => $profile,
            'targetId' => $targetId,
            'targetType' => $targetType
        ]);

        if ($existingMark) {
            $existingMark->setIsFavorite($isFavorite);
            $existingMark->setIsPinned($isPinned);
            if ($rating !== null) {
                $existingMark->setRating($rating);
            }
            $existingMark->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            return $existingMark;
        }

        $mark = new Mark($profile, $targetId, $targetType);
        if ($rating !== null) {
            $mark->setRating($rating);
        }
        $mark->setIsFavorite($isFavorite);
        $mark->setIsPinned($isPinned);
        $this->entityManager->persist($mark);
        $this->entityManager->flush();

        return $mark;
    }

    public function deleteMark(int $id): void
    {
        $mark = $this->markRepository->find($id);
        if (!$mark) {
            throw new Exception("Mark non trouvé.");
        }

        $this->entityManager->remove($mark);
        $this->entityManager->flush();
    }

    public function getMarksByTarget(int $targetId, string $targetType): array
    {
        return $this->markRepository->findBy([
            'targetId' => $targetId,
            'targetType' => $targetType,
        ]);
    }
}