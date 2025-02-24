<?php

namespace App\Controller;

use App\Entity\React;
use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
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
        private readonly RedisReactService     $redisReactService
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
            return $this->json(['error' => 'Profil non trouvé'], 404);
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

        $reaction = new React($profile, $resourceType, $resourceId, ReactTypeEnum::from($reactionType));
        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        return $this->json(['message' => 'Réaction ajoutée']);
    }

    #[Route('/{resourceId}/{resourceType}/likes', name: 'get_reactions', methods: ['GET'])]
    public function getReactions(string $resourceId, string $resourceType): JsonResponse
    {
        $reactions = $this->redisReactService->getReactionsFromCache($resourceId, $resourceType);

        if (empty($reactions)) {
            $reactionsFromDB = $this->reactRepository->findBy([
                'resourceId' => $resourceId,
                'resourceType' => ResourceTypeEnum::from($resourceType),
            ]);

            $reactions = array_map(fn($reaction) => [
                'profileId' => $reaction->getProfile()->getId(),
                'type' => $reaction->getType()->value,
                'createdAt' => $reaction->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $reactionsFromDB);
        }

        return new JsonResponse(['reactions' => $reactions]);
    }
}