<?php

namespace App\Controller;

use App\Config\RedisClientConfig;
use App\Constant\ErrorMessagesConstant;
use App\Enum\ReactTypeEnum;
use App\Repository\ProfileRepository;
use App\Repository\ReactRepository;
use App\Service\Redis\RedisReactService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/react')]
class ReactController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository      $profileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReactRepository        $reactRepository,
        private readonly RedisClientConfig      $redis,
        private readonly RedisReactService      $redisReactService
    )
    {
    }

    #[Route('/toggle', name: 'toggle_react', methods: ['POST'])]
    public function toggleReaction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $profileId = $data['profileId'] ?? null;
        $resourceType = $data['resourceType'] ?? null;
        $resourceId = $data['resourceId'] ?? null;
        $reactionType = $data['reactionType'] ?? ReactTypeEnum::LIKE->value;

        if (!$profileId || !$resourceType || !$resourceId) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            return $this->json(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        $existingReaction = $this->reactRepository->findOneBy([
            'profile' => $profile,
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
        ]);

        if ($existingReaction) {
            $this->entityManager->remove($existingReaction);
            $this->entityManager->flush();
            return $this->json(['message' => 'Réaction supprimée']);
        }

        $this->redisReactService->addReactionToCache(
            profileId: $profileId,
            receiverId: $profileId,
            resourceType: $resourceType,
            reactType: $reactionType,
            resourceId: $resourceId
        );

        return $this->json(['message' => 'Réaction ajoutée']);
    }

    #[Route('/{profileId}', name: 'get_reaction_notifications', methods: ['GET'])]
    public function getReactionsForProfile(string $profileId): JsonResponse
    {
        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            return $this->json(['error' => 'Profil non trouvé'], 404);
        }

        $keys = $this->redis->getClient()->keys("reactions:*");
        $reactionsArray = [];

        foreach ($keys as $key) {
            $reactions = $this->redisReactService->getReactionsFromCache(explode(":", $key)[2], explode(":", $key)[1]);

            foreach ($reactions as $reaction) {
                if ($reaction['actorId'] === $profileId) {
                    $reactionsArray[] = $reaction;
                }
            }
        }

        return $this->json(['reactions' => $reactionsArray]);
    }
}