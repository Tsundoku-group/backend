<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Conversation;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class MessageService
{
    public function __construct(
        private ConfRedisService       $redisChatService,
        private EntityManagerInterface $entityManager,
        private ConversationRepository $conversationRepository,
        private UserRepository         $userRepository,
    ) {}

    public function sendMessage(int $conversationId, array $data): array
    {
        if (empty($data['userEmail'])) {
            return ['error' => 'User email is required', 'status' => 400];
        }

        if (empty($data['message'])) {
            return ['error' => 'Message content is required', 'status' => 400];
        }

        $user = $this->userRepository->findOneUserByEmail($data['userEmail']);
        if (!$user) {
            return ['error' => ErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
        }

        $createdBy = $this->entityManager->getRepository(Profile::class)->findOneBy(['user' => $user]);
        if (!$createdBy) {
            return ['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return ['error' => 'Conversation not found.', 'status' => 404];
        }

        if (!$this->conversationRepository->isUserParticipant($conversationId, $createdBy)) {
            return ['error' => 'User is not a participant in this conversation.', 'status' => 403];
        }

        try {
            $dateTime = new DateTime('now', new DateTimeZone('Europe/Paris'));

            $messageData = [
                'id' => $data['id'],
                'content' => $data['message'],
                'sender_id' => $createdBy->getId(),
                'sender_email' => $data['userEmail'],
                'sent_by' => $createdBy->getUsername(),
                'sent_at' => $dateTime->format('Y-m-d H:i:s'),
                'isRead' => false,
                'isReadAt' => null,
            ];

            $this->redisChatService->addMessageToConversation($conversationId, $messageData);

            $conversation->setLastMessageAt($dateTime);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return ['success' => 'Message sent to conversation.'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function getMessages(int $conversationId, array $queryParams, User $user): array
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return ['error' => 'Conversation not found.', 'status' => 404];
        }

        try {
            $allMessages = $this->redisChatService->getMessagesFromConversation((string)$conversationId);
            if (empty($allMessages)) {
                return [];
            }

            $totalMessages = count($allMessages);
            $startIndex = max($totalMessages - ($queryParams['page'] ?? 1) * ($queryParams['limit'] ?? 10), 0);
            $pagedMessages = array_slice($allMessages, $startIndex, $queryParams['limit'] ?? 10);

            return array_map(fn($message) => [
                'id' => $message['id'],
                'content' => $message['content'],
                'sender_id' => $message['sender_id'],
                'sent_by' => $message['sent_by'],
                'sent_at' => $message['sent_at'],
                'isRead' => $message['isRead'],
                'sender_email' => $message['sender_email'],
                'isCurrentUser' => $message['sender_email'] === $user->getEmail(),
            ], $pagedMessages);
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function markMessagesRead(int $conversationId, string $userEmail): array
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

            if (!$user) {
                return ['error' => ErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
            }

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return ['error' => 'Conversation not found.', 'status' => 404];
            }

            if (!$this->conversationRepository->isUserParticipant($conversationId, $user)) {
                return ['error' => 'User is not a participant in this conversation.', 'status' => 403];
            }

            $this->redisChatService->markMessagesRead($conversationId, $userEmail);

            return ['success' => 'All messages marked as read.'];

        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }
}