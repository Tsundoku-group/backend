<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Follower\FollowProfileDTO;
use App\DTO\Follower\UnfollowProfileDTO;
use App\Entity\Follower;
use App\Repository\FollowerRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/followers')]
class FollowerController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FollowerRepository $followerRepository,
    ) {
    }

    #[Route('/{profileId}/followers', name: 'get_followers_paginated', methods: ['GET'])]
    public function getFollowersPaginated(int $profileId, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        try {
            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $followers = $this->followerRepository->findFollowersWithPagination($profileId, $limit, $offset);

            if (!$followers) {
                return new JsonResponse(['error' => 'No followers found.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($followers, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/followed', name: 'get_followed_paginated', methods: ['GET'])]
    public function getFollowedPaginated(int $profileId, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        try {
            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $followed = $this->followerRepository->findFollowedWithPagination($profileId, $limit, $offset);

            if (!$followed) {
                return new JsonResponse(['error' => 'No followed found.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($followed, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/follow/{profileId}', name: 'follow_profile', methods: ['POST'])]
    public function followProfile(int $profileId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new FollowProfileDTO($data, $profileId);

        if (!$dto->profileId || !$dto->followingId) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        try {
            $follower = $this->profileRepository->findOneBy(['id' => $dto->profileId]);
            $following = $this->profileRepository->findOneBy(['id' => $dto->followingId]);

            if (!$follower || !$following) {
                return new JsonResponse('Follower profile or following profile not found.', Response::HTTP_BAD_REQUEST);
            }

            $existingFollow = $this->entityManager->getRepository(Follower::class)->findOneBy([
                'follower' => $follower,
                'following' => $following,
            ]);

            if ($existingFollow) {
                return new JsonResponse('Already following this profile.', Response::HTTP_CONFLICT);
            }

            $follow = new Follower();
            $follow->setFollower($follower);
            $follow->setFollowing($following);

            $this->entityManager->persist($follow);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Successfully followed the profile.'], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/unfollow/{id}', name: 'unfollow_profile', methods: ['DELETE'])]
    public function unfollowProfile(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $friendship = $this->entityManager->getRepository(Follower::class)->find($id);
        $dto = new UnfollowProfileDTO($data);

        if (!$friendship || !$dto->followerId || !$dto->followingId) {
            return new JsonResponse('Followers not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            if (($friendship->getFollower()->getId() !== $dto->followerId && $friendship->getFollowing()->getId() !== $dto->followerId)
                || ($friendship->getFollower()->getId() !== $dto->followerId && $friendship->getFollowing()->getId() !== $dto->followingId)) {
                return new JsonResponse('You are not authorized to unfollow this friendship.', Response::HTTP_CONFLICT);
            }

            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Successfully unfollowed the profile.'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
