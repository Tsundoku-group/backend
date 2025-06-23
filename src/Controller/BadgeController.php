<?php

namespace App\Controller;

use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Entity\Badge;
use App\Repository\BadgeRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/badges')]
class BadgeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private readonly ProfileRepository $profileRepository
    ) {}

    #[Route('/{profileId}', methods: ['GET'])]
    public function getBadgesByProfile(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $badges = $this->badgeRepository->findBadgesByProfile($profileId, 10);

            if (empty($badges)) {
                return $this->json(['message' => 'No badges found for this profile.'], 404);
            }

            return $this->json($badges, 200);
        } catch (Exception $e) {
            return $this->json(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }
}
