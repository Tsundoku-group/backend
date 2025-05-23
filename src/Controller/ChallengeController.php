<?php 

namespace App\Controller;

use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Repository\ChallengeRepository;
use App\Repository\ProfileRepository;
use App\Service\ChallengeService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/challenges')]
class ChallengeController extends AbstractController
{
    public function __construct(
        private readonly ChallengeService $challengeService,
        private readonly ChallengeRepository $challengeRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('/{profileId}', methods: ['GET'])]
    public function getChallengesByProfile(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return new JsonResponse(['error' =>
            ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $challenges = $this->challengeRepository->findByProfileId($profileId);

            return new JsonResponse($challenges, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }
}