<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Conversation;
use App\Services\ConfRedisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/message')]
class MessageController extends AbstractController
{
    private $confRedisService;
    private $entityManager;

    public function __construct(ConfRedisService $redisChatService, EntityManagerInterface $entityManager)
    {
        $this->confRedisService = $redisChatService;
        $this->entityManager = $entityManager;
    }

    #[Route('/send/{conversationId}', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $conversationId, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userEmail = $data['userEmail'] ?? null;
            $messageContent = $data['message'] ?? null;

            if (empty($userEmail)) {
                return new JsonResponse(['error' => 'User email is required'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($messageContent)) {
                return new JsonResponse(['error' => 'Message content is required'], Response::HTTP_BAD_REQUEST);
            }

            if (!$conversationId) {
                return new Response('Missing conversation Id', Response::HTTP_BAD_REQUEST);
            }

            $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            if (!$createdBy) {
                return new Response('User not found.', Response::HTTP_NOT_FOUND);
            }

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
            if (!$conversation) {
                return new Response('Conversation not found.', Response::HTTP_NOT_FOUND);
            }

            if (!$conversation->getParticipants()->contains($createdBy)) {
                return new JsonResponse('User is not a participant in this conversation.', Response::HTTP_FORBIDDEN);
            }

            $dateTime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $formattedDate = $dateTime->format('Y-m-d H:i:s');

            $messageData = [
                'content' => $messageContent,
                'sender_email' => $userEmail,
                'sent_by' => $createdBy->getProfiles()->first()->getUsername(),
                'sent_at' => $formattedDate,
                'isRead' => false,
                'isReadAt' => null
            ];

            $this->confRedisService->addMessageToConversation($conversationId, $messageData);

            $conversation->setLastMessageAt($dateTime);

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return new Response('Message sent to conversation.', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get/{conversationId}', name: 'get_messages', methods: ['GET'])]
    public function getMessages(int $conversationId, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse('User not authenticated.', Response::HTTP_UNAUTHORIZED);
            }

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
            if (!$conversation) {
                return new JsonResponse('Conversation not found.', Response::HTTP_NOT_FOUND);
            }

            if (!$conversation->getParticipants()->contains($user)) {
                return new JsonResponse('User is not a participant in this conversation.', Response::HTTP_FORBIDDEN);
            }

            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $allMessages = $this->confRedisService->getMessagesFromConversation($conversationId);
            if (empty($allMessages)) {
                return new JsonResponse('No messages found for this conversation.', Response::HTTP_OK);
            }

            $allMessages = array_reverse($allMessages);

            $offset = ($page - 1) * $limit;
            $pagedMessages = array_slice($allMessages, $offset, $limit);


            return new JsonResponse($pagedMessages, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/get-last-messages/{conversationId}', name: 'get_last_messages', methods: ['GET'])]
    public function getLastMessages(int $conversationId): JsonResponse
    {
        try {
            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new JsonResponse('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            $messages = $this->confRedisService->getMessagesFromConversation($conversationId);
            if (!$messages) {
                return new JsonResponse('No messages found for this conversation.', Response::HTTP_OK);
            }

            $lastMessage = end($messages);

            $response = [
                'conversationId' => $conversationId,
                'lastMessage' => $lastMessage,
            ];

            return new JsonResponse($response);
        } catch (\Exception $e) {
            return new JsonResponse('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/mark-messages-read/{conversationId}', name: 'mark_messages_read', methods: ['POST'])]
    public function markMessagesRead(int $conversationId, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userEmail = $data['userEmail'];

            if (!$userEmail) {
                return new Response('User email is required.', Response::HTTP_BAD_REQUEST);
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

            if (!$user) {
                return new Response('User not found.', Response::HTTP_NOT_FOUND);
            }

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new Response('Conversation not found.', Response::HTTP_NOT_FOUND);
            }

            if (!$conversation->getParticipants()->contains($user)) {
                return new JsonResponse('User is not a participant in this conversation.', Response::HTTP_FORBIDDEN);
            }

            $this->confRedisService->markMessagesRead($conversationId, $userEmail);

            return new Response('All messages marked as read.', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}