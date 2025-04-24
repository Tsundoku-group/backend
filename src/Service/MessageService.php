<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\Entity\Conversation;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Service\Redis\RedisMessageService;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class MessageService
{
    public function __construct(
        private RedisMessageService $redisMessageService,
        private EntityManagerInterface $entityManager,
        private ConversationRepository $conversationRepository,
    ) {
    }

    public function sendMessage(int $conversationId, array $data): array
    {
        if (empty($data['content'])) {
            return ['error' => 'Le contenu du message est obligatoire', 'status' => 400];
        }

        $createdBy = $this->entityManager->getRepository(Profile::class)->findOneBy(['id' => $data['sender_id']]);
        if (!$createdBy) {
            return ['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return ['error' => 'Conversation introuvable', 'status' => 404];
        }

        if (!$this->conversationRepository->isUserParticipant($conversationId, $createdBy)) {
            return ['error' => "L'utilisateur ne participe pas à cette conversation", 'status' => 403];
        }

        try {
            $dateTime = new DateTime('now', new DateTimeZone('Europe/Paris'));

            $messageData = [
                'uuid' => $data['uuid'],
                'content' => $data['content'],
                'sender_id' => $createdBy->getId(),
                'sent_by' => $createdBy->getUsername(),
                'sent_at' => $dateTime->format('Y-m-d H:i:s'),
                'isRead' => false,
                'isReadAt' => null,
            ];

            $this->redisMessageService->addMessageToConversation($conversationId, $messageData);

            $conversation->setLastMessageAt($dateTime);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return ['success' => 'Message envoyé à la conversation'];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function getMessages(int $conversationId, array $queryParams, Profile $profile): array
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
        if (!$conversation) {
            return ['error' => 'Conversation introuvable', 'status' => 404];
        }

        try {
            $allMessages = $this->redisMessageService->getMessagesFromConversation((string) $conversationId);
            if (empty($allMessages)) {
                return [];
            }

            $totalMessages = count($allMessages);
            $startIndex = max($totalMessages - ($queryParams['page'] ?? 1) * ($queryParams['limit'] ?? 10), 0);
            $pagedMessages = array_slice($allMessages, $startIndex, $queryParams['limit'] ?? 10);

            return array_map(fn ($message) => [
                'id' => $message['uuid'],
                'content' => $message['content'],
                'sender_id' => $message['sender_id'],
                'sent_by' => $message['sent_by'],
                'sent_at' => $message['sent_at'],
                'isRead' => $message['isRead'],
                'isCurrentUser' => $message['sent_by'] === $profile->getUsername(),
            ], $pagedMessages);
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function markMessagesRead(int $conversationId, string $userEmail): array
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

            if (!$user) {
                return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
            }

            $profile = $this->entityManager->getRepository(Profile::class)->findOneBy(['user' => $user]);

            if (!$profile) {
                return ['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
            }

            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return ['error' => 'Conversation introuvable', 'status' => 404];
            }

            if (!$this->conversationRepository->isUserParticipant($conversationId, $profile)) {
                return ['error' => "L'utilisateur ne participe pas à cette conversation", 'status' => 403];
            }

            $this->redisMessageService->markMessagesRead($conversationId, $userEmail);

            return ['success' => 'Tous les messages sont marqués comme lus'];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }
}
