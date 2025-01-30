<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Friendship\RemoveFriendDTO;
use App\DTO\Friendship\SendFriendRequestDTO;
use App\Service\FriendshipService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/friendship')]
class FriendshipController extends AbstractController
{
    public function __construct(
        private readonly FriendshipService $friendshipService)
    {
    }

    #[Route('/{profileId}/request', name: 'send_friend_request', methods: ['POST'])]
    public function sendFriendRequest(int $profileId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new SendFriendRequestDTO($data, $profileId);

        if (!$dto->profileId || !$dto->friendId) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $this->friendshipService->sendFriendRequest($dto->profileId, $dto->friendId);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/accept', name: 'accept_friend_request', methods: ['POST'])]
    public function acceptFriendRequest(int $id): JsonResponse
    {
        try {
            $response = $this->friendshipService->acceptFriendRequest($id);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/reject', name: 'reject_friend_request', methods: ['POST'])]
    public function rejectFriendRequest(int $id): JsonResponse
    {
        try {
            $response = $this->friendshipService->rejectFriendRequest($id);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/remove', name: 'remove_friend', methods: ['DELETE'])]
    public function removeFriend(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new RemoveFriendDTO($data);

        try {
            $response = $this->friendshipService->removeFriend($id, $dto);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/list', name: 'list_friends', methods: ['GET'])]
    public function listFriends(int $profileId, Request $request): JsonResponse
    {
        try {
            $response = $this->friendshipService->listFriends($profileId, $request);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/list/requests', name: 'list_friend_requests', methods: ['GET'])]
    public function listFriendRequests(int $profileId): JsonResponse
    {
        try {
            $response = $this->friendshipService->listFriendRequests($profileId);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/suggestions', name: 'profile_suggestions', methods: ['GET'])]
    public function getProfileFriendsSuggestions(int $profileId, Request $request): JsonResponse
    {
        $limit = max((int) $request->query->get('limit', '20'), 1);
        $offset = max((int) $request->query->get('offset', '0'), 0);

        $friendsSuggestions = $this->friendshipService->getSuggestionsFriendsByProfile($profileId, $limit, $offset);

        return new JsonResponse(['suggestions' => $friendsSuggestions], Response::HTTP_OK);
    }
}
