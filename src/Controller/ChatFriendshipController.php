<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ChatFriendship;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/chat-friendship')]
class ChatFriendshipController extends AbstractController
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

        $requester = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $requesterEmail]);
        $receiver = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $receiverEmail]);

        if (!$requester || !$receiver) {
            return new Response('Requester or receiver not found.', Response::HTTP_NOT_FOUND);
        }

        $existingFriendship = $this->entityManager->getRepository(ChatFriendship::class)->findOneBy([
            'requester' => $requester,
            'receiver' => $receiver
        ]);

        $existingInverseFriendship = $this->entityManager->getRepository(ChatFriendship::class)->findOneBy([
            'requester' => $receiver,
            'receiver' => $requester
        ]);

        if ($existingFriendship || $existingInverseFriendship) {
            return new Response('ChatFriendship already exists or request already sent.', Response::HTTP_CONFLICT);
        }

        $friendship = new ChatFriendship();
        $friendship->setRequester($requester);
        $friendship->setReceiver($receiver);
        $friendship->setStatus(ChatFriendship::STATUS_PENDING);

        $this->entityManager->persist($friendship);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Friend request sent.'], Response::HTTP_CREATED);
    }

    #[Route('/accept/{id}', name: 'accept_friend_request', methods: ['POST'])]
    public function acceptFriendRequest(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(ChatFriendship::class)->find($id);

        if (!$friendship || $friendship->getStatus() !== 'pending') {
            return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
        }

        $friendship->setStatus('accepted');

        $this->entityManager->flush();

        return new Response('Friend request accepted.', Response::HTTP_OK);
    }

    #[Route('/reject/{id}', name: 'reject_friend_request', methods: ['POST'])]
    public function rejectFriendRequest(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(ChatFriendship::class)->find($id);

        if (!$friendship || $friendship->getStatus() !== 'pending') {
            return new Response('Friend request not found or already processed.', Response::HTTP_NOT_FOUND);
        }

        $friendship->setStatus('rejected');

        $this->entityManager->flush();

        return new Response('Friend request rejected.', Response::HTTP_OK);
    }

    #[Route('/remove/{id}', name: 'remove_friend', methods: ['DELETE'])]
    public function removeFriend(int $id): Response
    {
        $friendship = $this->entityManager->getRepository(ChatFriendship::class)->find($id);

        if (!$friendship || $friendship->getStatus() !== 'accepted') {
            return new Response('ChatFriendship not found or not accepted.', Response::HTTP_NOT_FOUND);
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
            return new JsonResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        $friendships = $this->entityManager->getRepository(ChatFriendship::class)->findBy(['requester' => $user, 'status' => 'accepted']);
        $friends = array_map(function ($friendship) {
            return [
                'id' => $friendship->getReceiver()->getId(),
                'email' => $friendship->getReceiver()->getEmail(),
                'userName' => $friendship->getReceiver()->getProfiles()->first()->getUsername(),
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

        $friendRequests = $this->entityManager->getRepository(ChatFriendship::class)->findBy([
            'receiver' => $user,
        ]);

        $requests = array_map(function ($friendship) {
            return [
                'id' => $friendship->getId(),
                'requester' => [
                    'id' => $friendship->getRequester()->getId(),
                    'email' => $friendship->getRequester()->getEmail(),
                    'username' => $friendship->getRequester()->getProfiles()->first()->getUsername(),
                ],
                'status' => $friendship->getStatus(),
                'createdAt' => $friendship->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $friendRequests);

        return new JsonResponse($requests, Response::HTTP_OK);
    }
}