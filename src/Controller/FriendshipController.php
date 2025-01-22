<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Service\FriendshipService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/friendship')]
class FriendshipController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProfileRepository $profileRepository;
    private FriendshipRepository $friendshipRepository;
    private FriendshipService $friendshipService;

    public function __construct(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, FriendshipRepository $friendshipRepository, FriendshipService $friendshipService)
    {
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;
        $this->friendshipRepository = $friendshipRepository;
        $this->friendshipService = $friendshipService;
    }

    #[Route('/request/{profileId}', name: 'send_friend_request', methods: ['POST'])]
    public function sendFriendRequest(int $profileId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $friendIdToRequest = $data['friendId'];

            if (!$profileId || !$friendIdToRequest) {
                return new JsonResponse('Invalid input.', Response::HTTP_BAD_REQUEST);
            }

            $requesterProfileUser = $this->profileRepository->findOneBy(['id' => $profileId]);
            $receiverProfileUser = $this->profileRepository->findOneBy(['id' => $friendIdToRequest]);

            if (!$requesterProfileUser || !$receiverProfileUser) {
                return new JsonResponse('Requester or receiver not found.', Response::HTTP_NOT_FOUND);
            }

            $existingFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
                'requester' => $requesterProfileUser,
                'receiver' => $receiverProfileUser,
            ]);

            $existingInverseFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
                'requester' => $receiverProfileUser,
                'receiver' => $requesterProfileUser,
            ]);

            if ($existingFriendship) {
                if (Friendship::STATUS_PENDING === $existingFriendship->getStatus()) {
                    return new JsonResponse('Request already sent. Status: pending', Response::HTTP_CONFLICT);
                }

                if (Friendship::STATUS_REJECTED === $existingFriendship->getStatus()) {
                    $existingFriendship->setStatus(Friendship::STATUS_PENDING);
                    $this->entityManager->flush();

                    return new JsonResponse('Friend request resent', Response::HTTP_CREATED);
                }

                if (Friendship::STATUS_ACCEPTED === $existingFriendship->getStatus()) {
                    return new JsonResponse('Friendship already exists', Response::HTTP_CONFLICT);
                }
            }

            if ($existingInverseFriendship) {
                if (Friendship::STATUS_PENDING === $existingInverseFriendship->getStatus()) {
                    return new JsonResponse('You already have a pending request for you', Response::HTTP_CONFLICT);
                }

                if (Friendship::STATUS_REJECTED === $existingInverseFriendship->getStatus()) {
                    $existingInverseFriendship->setStatus(Friendship::STATUS_PENDING);
                    $this->entityManager->flush();

                    return new JsonResponse('Friend request resent after rejection (inverse)', Response::HTTP_CREATED);
                }

                if (Friendship::STATUS_ACCEPTED === $existingInverseFriendship->getStatus()) {
                    return new JsonResponse('Friendship already exists (inverse)', Response::HTTP_CONFLICT);
                }
            }

            $friendship = new Friendship();
            $friendship->setRequester($requesterProfileUser);
            $friendship->setReceiver($receiverProfileUser);
            $friendship->setStatus(Friendship::STATUS_PENDING);

            $this->entityManager->persist($friendship);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Friend request sent'], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/accept/{id}', name: 'accept_friend_request', methods: ['POST'])]
    public function acceptFriendRequest(int $id): Response
    {
        try {
            $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

            if (!$friendship || 'pending' !== $friendship->getStatus()) {
                return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
            }

            $friendship->setStatus('accepted');
            $this->entityManager->flush();

            return new Response('Friend request accepted.', Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reject/{id}', name: 'reject_friend_request', methods: ['POST'])]
    public function rejectFriendRequest(int $id): Response
    {
        try {
            $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

            if (!$friendship || 'pending' !== $friendship->getStatus()) {
                return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
            }

            $friendship->setStatus('rejected');
            $this->entityManager->flush();

            return new Response('Friend request rejected.', Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove/{id}', name: 'remove_friend', methods: ['DELETE'])]
    public function removeFriend(int $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);
            $requesterId = $data['requesterId'];
            $receiverId = $data['receiverId'];

            if (!$friendship || 'accepted' !== $friendship->getStatus()) {
                return new Response('Friendship not found or not accepted.', Response::HTTP_NOT_FOUND);
            }

            if (($friendship->getRequester()->getId() !== $requesterId && $friendship->getReceiver()->getId() !== $requesterId)
                || ($friendship->getRequester()->getId() !== $receiverId && $friendship->getReceiver()->getId() !== $receiverId)) {
                return new Response('You are not authorized to remove this friendship.', Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return new Response('Friend removed.', Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list/{profileId}', name: 'list_friends', methods: ['GET'])]
    public function listFriends(int $profileId, Request $request): JsonResponse
    {
        try {
            $profile = $this->profileRepository->find($profileId);

            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
            }

            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $friendships = $this->friendshipRepository->findFriendshipUserProfileId($profileId, $limit, $offset);

            if (empty($friendships)) {
                return new JsonResponse(['message' => 'No friends found.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($friendships, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list-requests/{profileId}', name: 'list_friend_requests', methods: ['GET'])]
    public function listFriendRequests(int $profileId): JsonResponse
    {
        try {
            $profile = $this->profileRepository->find($profileId);

            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
            }

            $friendRequests = $this->entityManager->getRepository(Friendship::class)->findBy([
                'receiver' => $profile,
                'status' => 'pending',
            ]);

            if (0 === count($friendRequests)) {
                return new JsonResponse('No friend requests found.', Response::HTTP_OK);
            }

            $requests = array_map(function ($friendship) {
                $requesterProfile = $friendship->getRequester();
                $requesterUser = $requesterProfile->getUser();

                return [
                    'id' => $friendship->getId(),
                    'requester' => [
                        'id' => $requesterProfile->getId(),
                        'email' => $requesterUser ? $requesterUser->getEmail() : null,
                        'username' => $requesterProfile->getUsername(),
                    ],
                    'status' => $friendship->getStatus(),
                    'createdAt' => $friendship->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $friendRequests);

            return new JsonResponse($requests, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
