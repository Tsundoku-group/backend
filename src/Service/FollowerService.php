<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Follower;
use App\Enum\NotificationTypeEnum;
use App\Enum\ResourceTypeEnum;
use App\Repository\FollowerRepository;
use App\Repository\ProfileRepository;
use App\Service\Redis\RedisNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;

readonly class FollowerService
{
    public function __construct(
        private ProfileRepository $profileRepository,
        private FollowerRepository $followerRepository,
        private EntityManagerInterface $entityManager,
        private RedisNotificationService $redisNotificationService,
    ) {
    }

    public function getFollowersPaginated(int $profileId, Request $request): array
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return ['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        try {
            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $followers = $this->followerRepository->findFollowersWithPagination($profileId, $limit, $offset);

            if (empty($followers)) {
                return ['error' => "Aucun suiveur n'a été trouvé", 'status' => 404];
            }

            return $followers;
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function getFollowedPaginated(int $profileId, Request $request): array
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return ['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        try {
            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $followed = $this->followerRepository->findFollowedWithPagination($profileId, $limit, $offset);

            if (empty($followed)) {
                return ['error' => "Aucun suivi n'a été trouvé", 'status' => 404];
            }

            return $followed;
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function followProfile(int $profileId, Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        $followingId = $data['followingId'] ?? null;

        if (!$profileId || !$followingId) {
            return ['error' => ErrorMessagesConstant::INVALID_DATA, 'status' => 400];
        }

        $follower = $this->profileRepository->find($profileId);
        $following = $this->profileRepository->find($followingId);

        if (!$follower || !$following) {
            return ['error' => "Le profil du suiveur ou du suiveur n'a pas été trouvé", 'status' => 404];
        }

        $existingFollow = $this->followerRepository->findOneBy([
            'follower' => $follower,
            'following' => $following,
        ]);

        if ($existingFollow) {
            return ['error' => 'Profil déjà suivi', 'status' => 409];
        }

        try {
            $follow = new Follower();
            $follow->setFollower($follower);
            $follow->setFollowing($following);

            $this->entityManager->persist($follow);
            $this->entityManager->flush();

            $this->redisNotificationService->addNotificationToCache(
                receiverId: $following->getId(),
                actorId: $follower->getId(),
                notificationTypeEnum: NotificationTypeEnum::FOLLOW->value,
                resourceId: null,
                resourceTypeEnum: ResourceTypeEnum::FOLLOW->value,
            );

            return ['message' => 'Le profil a été suivi avec succès', 'status' => 201];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function unfollowProfile(int $id, Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        $followerId = $data['followerId'] ?? null;
        $followingId = $data['followingId'] ?? null;

        $friendship = $this->followerRepository->find($id);

        if (!$friendship || !$followerId || !$followingId) {
            return ['error' => 'Followers introuvables', 'status' => 404];
        }

        if (($friendship->getFollower()->getId() !== $followerId && $friendship->getFollowing()->getId() !== $followerId)
            || ($friendship->getFollower()->getId() !== $followerId && $friendship->getFollowing()->getId() !== $followingId)) {
            return ['error' => "Vous n'êtes pas autorisé à supprimer ce profil", 'status' => 403];
        }

        try {
            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return ['message' => 'Le profil a été supprimé avec succès'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }
}
