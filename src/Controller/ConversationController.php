<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ProfileRepository;
use App\Service\ConfRedisService;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/conversation')]
class ConversationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ConfRedisService $confRedisService;
    private ProfileRepository $profileRepository;
    private ConversationRepository $conversationRepository;
    private const USER_NOT_FOUND = 'USER_NOT_FOUND';

    public function __construct(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, ConversationRepository $conversationRepository, ConfRedisService $redisChatService)
    {
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;
        $this->conversationRepository = $conversationRepository;
        $this->confRedisService = $redisChatService;
    }


    #[Route('/create', name: 'create_conversation', methods: 'POST')]
    public function createConversation(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['participants']) || !isset($data['email']) || !is_array($data['participants'])) {
                return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
            }

            $userEmail = $data['email'];
            $createdBy = $this->profileRepository->findProfileByEmail($userEmail);

            if (!$createdBy) {
                return new JsonResponse(['message' => self::USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            $participantsIds = $data['participants'];
            if (!in_array($createdBy->getId(), $participantsIds)) {
                $participantsIds[] = $createdBy->getId();
            }

            $participants = [];
            foreach ($participantsIds as $participantId) {
                $participant = $this->entityManager->getRepository(Profile::class)->find($participantId);
                if ($participant) {
                    $participants[] = $participant;
                }
            }

            $existingConversation = $this->conversationRepository->findOneByParticipants($participants);

            if ($existingConversation) {
                return new Response('La conversation existe déjà', Response::HTTP_CONFLICT);
            }

            $conversation = new Conversation();
            $conversation->setCreatedBy($createdBy);
            $conversation->setCreatedAt(new DateTimeImmutable());

            foreach ($participants as $participant) {
                $conversation->addParticipant($participant);
            }

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Conversation created', 'conversationId' => $conversation->getId()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-all/{id}', name: 'get_all_conversations_with_last_messages', methods: ['GET'])]
    public function getAllConversationsWithLastMessages(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if (!$user) {
                return new JsonResponse(['message' => self::USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);

            $conversations = $this->conversationRepository->findConversationsByUserOrderedByLastMessage($user, $page, $limit);
            if (!$conversations) {
                return new JsonResponse(['conversations' => []], Response::HTTP_OK);
            }

            $lastMessages = [];
            foreach ($conversations as $conversation) {
                $messages = $this->confRedisService->getMessagesFromConversation($conversation->getId());
                if ($messages) {
                    $lastMessages[$conversation->getId()] = end($messages);
                } else {
                    $lastMessages[$conversation->getId()] = null;
                }
            }

            usort($conversations, function ($a, $b) use ($lastMessages) {
                $lastMessageA = $lastMessages[$a->getId()] ?? null;
                $lastMessageB = $lastMessages[$b->getId()] ?? null;

                $dateA = $lastMessageA ? $lastMessageA['sent_at'] : '1970-01-01';
                $dateB = $lastMessageB ? $lastMessageB['sent_at'] : '1970-01-01';

                return strtotime($dateB) - strtotime($dateA);
            });

            $offset = ($page - 1) * $limit;
            $limitedConversations = array_slice($conversations, $offset, $limit);

            $conversationData = array_filter(array_map(function ($conversation) use ($user, $lastMessages) {
                $createdBy = $conversation->getCreatedBy();
                $createdByUser = $createdBy->getUser();

                return !$conversation->getIsArchived() ? [
                    'id' => $conversation->getId(),
                    'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'lastMessageAt' => $lastMessages[$conversation->getId()]['sent_at'] ?? null,
                    'lastMessage' => $lastMessages[$conversation->getId()],
                    'createdBy' => [
                        'id' => $createdBy->getId(),
                        'email' => $createdByUser ? $createdByUser->getEmail() : null,
                        'username' => $createdBy->getUsername(),
                    ],
                    'participants' => array_map(function ($participant) {
                        $participantUser = $participant->getUser();

                        return [
                            'id' => $participant->getId(),
                            'email' => $participantUser ? $participantUser->getEmail() : null,
                            'username' => $participant->getUsername(),
                        ];
                    }, $conversation->getParticipants()->toArray()),
                    'isArchived' => $conversation->getIsArchived(),
                    'isMutedUntil' => $conversation->getMutedUntil(),
                ] : null;
            }, $limitedConversations));

            return new JsonResponse(['conversations' => $conversationData], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-one/{id}', name: 'get_conversation_by_id', methods: ['GET'])]
    public function getConversationById(int $id): JsonResponse
    {
        try {
            $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

            if (!$conversation) {
                return new JsonResponse(['message' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
            }

            $createdBy = $conversation->getCreatedBy();
            if (!$createdBy || !$createdBy->getUser()) {
                return new JsonResponse(['message' => 'Creator not found'], Response::HTTP_NOT_FOUND);
            }

            $conversationData = [
                'id' => $conversation->getId(),
                'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                'lastMessageAt' => $conversation->getLastMessageAt(),
                'createdBy' => [
                    'id' => $createdBy->getId(),
                    'email' => $createdBy->getUser()->getEmail(),
                    'username' => $createdBy->getUsername(),
                ],
                'participants' => array_map(function ($participant) {
                    if (!$participant || !$participant->getUser()) {
                        return [];
                    }

                    return [
                        'id' => $participant->getId(),
                        'email' => $participant->getUser()->getEmail(),
                        'username' => $participant->getUsername(),
                    ];
                }, $conversation->getParticipants()->toArray()),
                'isArchived' => $conversation->getIsArchived(),
                'isMutedUntil' => $conversation->getMutedUntil(),
            ];

            return new JsonResponse($conversationData, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'delete_conversation', methods: ['DELETE'])]
    public function deleteConversationById(int $id): Response
    {
        try {
            $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

            if (!$conversation) {
                return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($conversation);
            $this->entityManager->flush();

            return new Response('Conversation deleted', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/archived/{userId}', name: 'get_archived_conversations_by_user_id', methods: ['GET'])]
    public function getArchivedConversationsByUserId(int $userId): Response
    {
        try {
            $conversations = $this->conversationRepository->findArchivedConversationsByUserId($userId);

            if (empty($conversations)) {
                return new Response('No archived conversations found for this user', Response::HTTP_NOT_FOUND);
            }

            $lastMessages = [];
            foreach ($conversations as $conversation) {
                $messages = $this->confRedisService->getMessagesFromConversation($conversation->getId());
                $lastMessages[$conversation->getId()] = $messages ? end($messages) : null;
            }

            usort($conversations, function ($a, $b) use ($lastMessages) {
                $lastMessageA = $lastMessages[$a->getId()] ?? null;
                $lastMessageB = $lastMessages[$b->getId()] ?? null;

                $dateA = $lastMessageA ? $lastMessageA['sent_at'] : '1970-01-01';
                $dateB = $lastMessageB ? $lastMessageB['sent_at'] : '1970-01-01';

                return strtotime($dateB) - strtotime($dateA);
            });

            $conversationData = array_map(function ($conversation) use ($lastMessages) {
                $createdBy = $conversation->getCreatedBy();
                if (!$createdBy || !$createdBy->getUser()) {
                    return [];
                }

                return [
                    'id' => $conversation->getId(),
                    'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'lastMessageAt' => $lastMessages[$conversation->getId()]['sent_at'] ?? null,
                    'lastMessage' => $lastMessages[$conversation->getId()],
                    'createdBy' => [
                        'id' => $createdBy->getId(),
                        'email' => $createdBy->getUser()->getEmail(),
                        'username' => $createdBy->getUsername(),
                    ],
                    'participants' => array_map(function ($participant) {
                        if (!$participant || !$participant->getUser()) {
                            return [];
                        }

                        return [
                            'id' => $participant->getId(),
                            'email' => $participant->getUser()->getEmail(),
                            'username' => $participant->getUsername(),
                        ];
                    }, $conversation->getParticipants()->toArray()),
                    'isArchived' => $conversation->getIsArchived(),
                ];
            }, $conversations);

            return new JsonResponse(['conversations' => $conversationData], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/archive/{conversationId}', name: 'archive_conversation', methods: ['POST'])]
    public function archiveConversation(int $conversationId): Response
    {
        try {
            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            if ($conversation->getIsArchived()) {
                return new Response('Conversation is already archived', Response::HTTP_FORBIDDEN);
            }

            $conversation->setIsArchived(true);
            $this->entityManager->flush();

            return new Response('Conversation archived', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unarchive/{conversationId}', name: 'unarchive_conversation', methods: ['POST'])]
    public function unarchiveConversation(int $conversationId): Response
    {
        try {
            $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            if (!$conversation->getIsArchived()) {
                return new Response('Conversation déjà unarchived', Response::HTTP_CONFLICT);
            }

            $conversation->setIsArchived(false);
            $this->entityManager->flush();

            return new Response('Conversation unarchived', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unarchive-all/{id}', name: 'unarchive_all_conversations', methods: ['POST'])]
    public function unarchiveAllConversations(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $conversations = $entityManager->getRepository(Conversation::class)->findBy([
                'isArchived' => true,
            ]);

            if (empty($conversations)) {
                return new JsonResponse(['message' => 'Aucune conversation trouvée.'], Response::HTTP_NOT_FOUND);
            }

            foreach ($conversations as $conversation) {
                $conversation->setIsArchived(false);
                $entityManager->persist($conversation);
            }

            $entityManager->flush();

            return new JsonResponse(['message' => 'Toutes les conversations ont été désarchivées avec succès.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Route('/mute/{conversationId}', name: 'mute_conversation', methods: ['POST'])]
    public function muteConversation(int $conversationId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new JsonResponse(['message' => 'Aucune conversation trouvée.'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['message' => 'Requête invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $duration = $data['duration'] ?? null;

            if (!$duration) {
                return new JsonResponse(['message' => 'Durée de sourdine non spécifiée.'], Response::HTTP_BAD_REQUEST);
            }

            $muteUntil = null;

            if ('eternal' === $duration) {
                $conversation->setMutedUntil(new DateTime('9999-12-31 23:59:59'));
            } else {
                $timezone = new DateTimeZone('Europe/Paris');
                $muteUntil = (new DateTime('now', $timezone))->modify("+{$duration} hours");

                if (!$muteUntil) {
                    return new JsonResponse(['message' => 'Durée invalide.'], Response::HTTP_BAD_REQUEST);
                }

                $conversation->setIsMuted(true);
                $conversation->setMutedUntil($muteUntil);
            }

            $entityManager->persist($conversation);
            $entityManager->flush();

            return new JsonResponse(['duration' => $muteUntil->format('Y-m-d H:i:s')], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unmute/{conversationId}', name: 'unmute_conversation', methods: ['POST'])]
    public function unmuteConversation(int $conversationId, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);

            if (!$conversation) {
                return new JsonResponse(['message' => 'Aucune conversation trouvée.'], Response::HTTP_NOT_FOUND);
            }

            $conversation->setIsMuted(false);
            $conversation->setMutedUntil(null);

            $entityManager->persist($conversation);
            $entityManager->flush();

            return new JsonResponse(['message' => 'La sourdine de la conversation a été annulée avec succès.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
