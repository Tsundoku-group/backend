<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(EntityManagerInterface $entityManager, ProfileRepository $profileRepository)
    {
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;
    }

    #[Route('/request', name: 'send_friend_request', methods: ['POST'])]
    public function sendFriendRequest(Request $request): JsonResponse
    {
        try {
            $requesterUsername = $request->headers->get('requester-username');
            $receiverUsername = $request->headers->get('receiver-username');

            if (!$requesterUsername || !$receiverUsername) {
                return new JsonResponse('Invalid input.', Response::HTTP_BAD_REQUEST);
            }

            $requesterProfileUser = $this->profileRepository->findOneBy(['username' => $requesterUsername]);
            $receiverProfileUser = $this->profileRepository->findOneBy(['username' => $receiverUsername]);

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

            if ($existingFriendship || $existingInverseFriendship) {
                return new JsonResponse('Friendship already exists or request already sent.', Response::HTTP_CONFLICT);
            }

            $friendship = new Friendship();
            $friendship->setRequester($requesterProfileUser);
            $friendship->setReceiver($receiverProfileUser);
            $friendship->setStatus(Friendship::STATUS_PENDING);

            $this->entityManager->persist($friendship);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Friend request sent.'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove/{id}', name: 'remove_friend', methods: ['DELETE'])]
    public function removeFriend(int $id): Response
    {
        try {
            $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

            if (!$friendship || 'accepted' !== $friendship->getStatus()) {
                return new Response('Friendship not found or not accepted.', Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return new Response('Friend removed.', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list/{id}', name: 'list_friends', methods: ['GET'])]
    public function listFriends(int $id): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
            }

            $friendships = $this->entityManager->getRepository(Friendship::class)->findBy([
                'requester' => $user,
                'status' => 'accepted'
            ]);

            if (empty($friendships)) {
                return new JsonResponse(['message' => 'No friends found.'], Response::HTTP_NOT_FOUND);
            }

            $friends = array_map(function ($friendship) {
                $receiverProfile = $friendship->getReceiver();
                $receiverEmail = $receiverProfile->getUser() ? $receiverProfile->getUser()->getEmail() : null;

                return [
                    'id' => $receiverProfile->getId(),
                    'email' => $receiverEmail,
                    'username' => $receiverProfile->getUsername(),
                ];
            }, $friendships);

            return new JsonResponse($friends, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list-requests/{userId}', name: 'list_friend_requests', methods: ['GET'])]
    public function listFriendRequests(int $userId): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($userId);

            if (!$user) {
                return new JsonResponse('User not found.', Response::HTTP_NOT_FOUND);
            }

            $friendRequests = $this->entityManager->getRepository(Friendship::class)->findBy([
                'receiver' => $user,
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}