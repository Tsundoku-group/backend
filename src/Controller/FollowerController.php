<?php

namespace App\Controller;

use App\Entity\Follower;
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
    private ProfileRepository $profileRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(ProfileRepository $profileRepository, EntityManagerInterface $entityManager)
    {
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/{profileId}', name: 'get_followers_paginated', methods: ['GET'])]
    public function getFollowersPaginated(int $profileId, Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);

            $profile = $this->profileRepository->find($profileId);

            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
            }

            $followers = $this->entityManager->getRepository(Follower::class)->findFollowersWithPagination($profileId, $page, $limit);

            $followerList = array_map(function ($follower) {
                $profile = $follower->getFollower();

                return [
                    'id' => $profile->getId(),
                    'username' => $profile->getUsername(),
                    'firstName' => $profile->getFirstName(),
                    'lastName' => $profile->getLastName(),
                ];
            }, $followers);

            return new JsonResponse([
                'data' => $followerList,
                'page' => $page,
                'limit' => $limit,
                'count' => count($followerList),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/follow/{profileId}', name: 'follow_profile', methods: ['POST'])]
    public function followProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $followerUsername = $data['followerUsername'] ?? null;
            $followingUsername = $data['followingUsername'] ?? null;

            if (!$followerUsername || !$followingUsername) {
                return new JsonResponse('Invalid input.', Response::HTTP_BAD_REQUEST);
            }

            $follower = $this->profileRepository->findOneBy(['username' => $followerUsername]);
            $following = $this->profileRepository->findOneBy(['username' => $followingUsername]);

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
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unfollow/{profileId}', name: 'unfollow_profile', methods: ['POST'])]
    public function unfollowProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $followerUsername = $data['followerUsername'] ?? null;
            $followingUsername = $data['followingUsername'] ?? null;

            if (!$followerUsername || !$followingUsername) {
                return new JsonResponse('Invalid input.', Response::HTTP_BAD_REQUEST);
            }

            $follower = $this->profileRepository->findOneBy(['username' => $followerUsername]);
            $following = $this->profileRepository->findOneBy(['username' => $followingUsername]);

            if (!$follower || !$following) {
                return new JsonResponse('Follower profile or following profile not found.', Response::HTTP_BAD_REQUEST);
            }

            $follow = $this->entityManager->getRepository(Follower::class)->findOneBy([
                'follower' => $follower,
                'following' => $following,
            ]);

            if (!$follow) {
                return new JsonResponse('Not following this profile.', Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($follow);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Successfully unfollowed the profile.'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
