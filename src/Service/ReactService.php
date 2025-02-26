<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\React;
use App\Enum\NotificationTypeEnum;
use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
use App\Repository\ProfileRepository;
use App\Repository\ReactRepository;
use App\Service\Redis\RedisNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class ReactService
{
    public function __construct(
        private ProfileRepository        $profileRepository,
        private ReactRepository          $reactRepository,
        private EntityManagerInterface   $entityManager,
        private RedisNotificationService $redisNotificationService
    )
    {
    }

    public function toggleReaction(array $data): JsonResponse
    {
        try {
            $profileId = $data['actorId'] ?? null;
            $receiverId = $data['receiverId'] ?? null;
            $resourceType = $data['resourceType'] ?? null;
            $resourceId = $data['resourceId'] ?? null;
            $reactType = $data['reactType'] ?? null;

            if (!$profileId || !$receiverId || !$resourceType || !$resourceId || !$reactType) {
                return new JsonResponse(['error' => 'Données manquantes', 'status' => 400]);
            }

            $profile = $this->profileRepository->find($profileId);
            if (!$profile) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404]);
            }

            $receiver = $this->profileRepository->find($receiverId);
            if (!$receiver) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404]);
            }

            $existingReaction = $this->reactRepository->findOneBy([
                'actor' => $profile,
                'receiver' => $receiver,
                'resourceType' => $resourceType,
                'resourceId' => $resourceId,
                'reactType' => $reactType,
            ]);

            if ($existingReaction) {
                $this->entityManager->remove($existingReaction);
                $this->entityManager->flush();
                return new JsonResponse(['message' => 'Réaction supprimée'], 200);
            }

            $reaction = new React($profile, $receiver, $resourceId, ResourceTypeEnum::from($resourceType), ReactTypeEnum::from($reactType));

            $this->entityManager->persist($reaction);
            $this->entityManager->flush();

            $this->redisNotificationService->addNotificationToCache(
                receiverId: $receiver->getId(),
                actorId: $profile->getId(),
                notificationTypeEnum: NotificationTypeEnum::LIKE->value,
                resourceId: $resourceId,
                resourceTypeEnum: ResourceTypeEnum::POST->value,
            );

            return new JsonResponse(['message' => 'Réaction ajoutée et notification mise en cache', 'status' => 201]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500]);
        }
    }
}