<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Service\FollowerService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/followers')]
class FollowerController extends AbstractController
{
    public function __construct(
        private readonly FollowerService $followerService,
    ) {
    }

    #[Route('/{profileId}/followers', name: 'get_followers_paginated', methods: ['GET'])]
    public function getFollowersPaginated(int $profileId, Request $request): JsonResponse
    {
        try {
            $response = $this->followerService->getFollowersPaginated($profileId, $request);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/followed', name: 'get_followed_paginated', methods: ['GET'])]
    public function getFollowedPaginated(int $profileId, Request $request, FollowerService $followerService): JsonResponse
    {
        try {
            $response = $followerService->getFollowedPaginated($profileId, $request);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/follow', name: 'follow_profile', methods: ['POST'])]
    public function followProfile(int $profileId, Request $request): JsonResponse
    {
        try {
            $response = $this->followerService->followProfile($profileId, $request);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse(['message' => $response['message']], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/unfollow', name: 'unfollow_profile', methods: ['DELETE'])]
    public function unfollowProfile(int $id, Request $request): JsonResponse
    {
        try {
            $response = $this->followerService->unfollowProfile($id, $request);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse(['message' => $response['message']], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
