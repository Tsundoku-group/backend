<?php

namespace App\Controller;

use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Entity\Challenge;
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
    ) {}

    #[Route('/{profileId}/active', methods: ['GET'])]
    public function getActiveChallengesByProfile(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $activeChallenges = $this->challengeRepository->findActiveChallengesByProfile($profileId);

            $data = array_map(fn(Challenge $c): array => [
                'id'        => $c->getId(),
                'name'      => $c->getName(),
                'type'      => $c->getType()->value,
                'status'    => $c->getStatus()->value,
                'startAt'   => $c->getStartAt()->format(DATE_ATOM),
                'endAt'     => $c->getEndAt()->format(DATE_ATOM),
                'creator'  => [
                    'id'        => $c->getCreator()->getId(),
                    'username'  => $c->getCreator()->getUsername(),
                ],
                'constraint' => [
                    'action'      => $c->getConstraint()->getAction()->value,
                    'contentType' => $c->getConstraint()->getContentType()->value,
                    'frequency'   => $c->getConstraint()->getFrequency()->value,
                    'targetCount' => $c->getConstraint()->getTargetCount(),
                ],
            ], $activeChallenges);

            return $this->json($data, 200);
        } catch (Exception $e) {
            return $this->json(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }
}
