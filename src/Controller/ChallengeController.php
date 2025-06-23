<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Dto\CreateChallengeDto;
use App\Enum\ChallengeStatusEnum;
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
    public function getActiveChallengesByProfile(int $profileId, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $offset = (int) $request->query->get('offset', 0);
            $limit = (int) $request->query->get('limit', 5);

            $activeChallenges = $this->challengeRepository->findActiveChallengesByProfilePaginated(
                $profileId,
                $offset,
                $limit
            );

            $totalCount = $this->challengeRepository->countChallengesByProfileAndStatus($profileId, [
                \App\Enum\ChallengeStatusEnum::PENDING->value,
                \App\Enum\ChallengeStatusEnum::ONGOING->value,
            ]);

            $data = $this->challengeService->formatChallengesData($activeChallenges);

            return $this->json([
                'data' => $data,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'hasMore' => ($offset + $limit) < $totalCount
                ]
            ], 200);
        } catch (Exception $e) {
            return $this->json(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }

    #[Route('/{profileId}/inactive', methods: ['GET'])]
    public function getInactiveChallengesByProfile(int $profileId, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $offset = (int) $request->query->get('offset', 0);
            $limit = (int) $request->query->get('limit', 5);

            $inactiveChallenges = $this->challengeRepository->findInactiveChallengesByProfilePaginated(
                $profileId,
                $offset,
                $limit
            );

            $totalCount = $this->challengeRepository->countChallengesByProfileAndStatus($profileId, [
                \App\Enum\ChallengeStatusEnum::SUCCESS->value,
                \App\Enum\ChallengeStatusEnum::FAILED->value,
                \App\Enum\ChallengeStatusEnum::CANCELED->value,
            ]);

            $data = $this->challengeService->formatChallengesData($inactiveChallenges);

            return $this->json([
                'data' => $data,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'hasMore' => ($offset + $limit) < $totalCount
                ]
            ], 200);
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
                return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }
            $creator = $this->profileRepository->findOneBy([
                'user'          => $user,
                'activeProfile' => true,
            ]);
            if (!$creator) {
                return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            if ($creator->getUser() !== $user) {
                return $this->json(['error' => SecurityErrorMessagesConstant::ACCESS_DENIED], 401);
            }

            $challenge = $this->challengeService->createChallenge($dto, $creator);

            return $this->json(['id' => $challenge->getId()], 201);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{challengeId}', methods: ['PATCH'])]
    public function updateChallenge(int $challengeId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => SecurityErrorMessagesConstant::INSUFFICIENT_PERMISSIONS], 403);
        }

        $challenge = $this->challengeRepository->find($challengeId);

        if (!$challenge) {
            return $this->json(GenericErrorMessagesConstant::NOT_FOUND, 404);
        }

        if ($challenge->getCreator()->getUser() !== $user) {
            return $this->json(SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS, 401);
        }

        if (!in_array($challenge->getStatus()->value, [ChallengeStatusEnum::PENDING->value, ChallengeStatusEnum::ONGOING->value])) {
            return $this->json(['error' => SecurityErrorMessagesConstant::ACCESS_DENIED], 403);
        }

        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                \App\Dto\UpdateChallengeDto::class,
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

            $updatedChallenge = $this->challengeService->updateChallenge($challenge, $dto);

            return $this->json(['id' => $updatedChallenge->getId()], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }


    #[Route('/{challengeId}', methods: ['DELETE'])]
    public function deleteChallenge(int $challengeId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => SecurityErrorMessagesConstant::INSUFFICIENT_PERMISSIONS], 403);
        }

        $challenge = $this->challengeRepository->find($challengeId);
        if (!$challenge) {
            return $this->json(GenericErrorMessagesConstant::NOT_FOUND, 404);
        }

        if ($challenge->getCreator()->getUser() !== $user) {
            return $this->json(SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS, 401);
        }

        try {
            $this->challengeService->deleteChallenge($challenge);
            return $this->json(['message' => 'Challenge successfully deleted.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
