<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/request', name: 'send_friend_request', methods: ['POST'])]
    public function sendFriendRequest(Request $request): Response
    {
        $requesterEmail = $request->headers->get('requester-email');
        $receiverEmail = $request->headers->get('receiver-email');

        if (!$requesterEmail || !$receiverEmail) {
            return new Response('Invalid input.', Response::HTTP_BAD_REQUEST);
        }

        $requesterUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $requesterEmail]);
        $receiverUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $receiverEmail]);

        if (!$requesterUser || !$receiverUser) {
            return new Response('Requester or receiver not found.', Response::HTTP_NOT_FOUND);
        }

        $existingFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
            'requester' => $requesterUser,
            'receiver' => $receiverUser,
        ]);

        $existingInverseFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
            'requester' => $receiverUser,
            'receiver' => $requesterUser,
        ]);

        if ($existingFriendship || $existingInverseFriendship) {
            return new Response('Friendship already exists or request already sent.', Response::HTTP_CONFLICT);
        }

        $requesterProfile = $requesterUser->getProfiles()->first();
        $receiverProfile = $receiverUser->getProfiles()->first();

        if (!$requesterProfile || !$receiverProfile) {
            return new JsonResponse(['message' => 'Profile not found'], Response::HTTP_NOT_FOUND);
        }

        $friendship = new Friendship();
        $friendship->setRequester($requesterProfile);
        $friendship->setReceiver($receiverProfile);
        $friendship->setStatus(Friendship::STATUS_PENDING);

        $this->entityManager->persist($friendship);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Friend request sent.'], Response::HTTP_CREATED);
    }

    #[Route('/accept/{id}', name: 'accept_friend_request', methods: ['POST'])]
    public function acceptFriendRequest(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

        if (!$friendship || 'pending' !== $friendship->getStatus()) {
            return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
        }

        $friendship->setStatus('accepted');

        $this->entityManager->flush();

        return new Response('Friend request accepted.', Response::HTTP_OK);
    }

    #[Route('/reject/{id}', name: 'reject_friend_request', methods: ['POST'])]
    public function rejectFriendRequest(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

        if (!$friendship || 'pending' !== $friendship->getStatus()) {
            return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
        }

        $friendship->setStatus('rejected');

        $this->entityManager->flush();

        return new Response('Friend request rejected.', Response::HTTP_OK);
    }

    #[Route('/remove/{id}', name: 'remove_friend', methods: ['DELETE'])]
    public function removeFriend(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);

        if (!$friendship || 'accepted' !== $friendship->getStatus()) {
            return new Response('Friendship not found or not accepted.', Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($friendship);
        $this->entityManager->flush();

        return new Response('Friend removed.', Response::HTTP_OK);
    }

    #[Route('/list/{userId}', name: 'list_friends', methods: ['GET'])]
    public function listFriends(int $userId): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $friendships = $this->entityManager->getRepository(Friendship::class)->findBy(['requester' => $user, 'status' => 'accepted']);

        $friends = array_map(function ($friendship) {
            $receiverProfile = $friendship->getReceiver();

            $receiverEmail = $receiverProfile->getUser() ? $receiverProfile->getUser()->getEmail() : null;

            return [
                'id' => $receiverProfile->getId(),
                'email' => $receiverEmail,
                'userName' => $receiverProfile->getUser() ? $receiverProfile->getUser()->getProfiles()->first()->getUsername() : null,
            ];
        }, $friendships);

        return new JsonResponse($friends, Response::HTTP_OK);
    }

    #[Route('/list-requests/{userId}', name: 'list_friend_requests', methods: ['GET'])]
    public function listFriendRequests(int $userId): JsonResponse
    {
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
    }
}
