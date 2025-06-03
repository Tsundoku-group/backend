<?php

namespace App\Controller;

use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Dto\CreateChallengeDto;
use App\Entity\Challenge;
use App\Repository\ChallengeRepository;
use App\Repository\ProfileRepository;
use App\Service\ChallengeService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/challenges')]
class ChallengeController extends AbstractController
{
    public function __construct(
        private readonly ChallengeService $challengeService,
        private readonly ChallengeRepository $challengeRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
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

    #[Route('/{profileId}/inactive', methods: ['GET'])]
    public function getInactiveChallengesByProfile(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $activeChallenges = $this->challengeRepository->findInactiveChallengesByProfile($profileId);

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

    #[Route('/constraints', methods: ['GET'])]
    public function getChallengeConstraints(): JsonResponse
    {
        try {
            $constraints = $this->challengeService->getChallengeConstraints();
            return $this->json($constraints, 200);
        } catch (Exception $e) {
            return $this->json(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }

    #[Route('', methods: ['POST'])]
    public function createChallenge(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                CreateChallengeDto::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                return $this->json(['errors' => $messages], 400);
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Non authentifié'], 401);
            }
            $creator = $this->profileRepository->findOneBy([
                'user'          => $user,
                'activeProfile' => true,
            ]);
            if (!$creator) {
                return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            $challenge = $this->challengeService->createChallenge($dto, $creator);

            return $this->json(['id' => $challenge->getId()], 201);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
