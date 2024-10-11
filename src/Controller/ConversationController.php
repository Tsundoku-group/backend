<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/conversation')]
class ConversationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ConversationRepository $conversationRepository;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, ConversationRepository $conversationRepository, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/create', name: 'create_conversation', methods: 'POST')]
    public function createConversation(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['participants'])) {
            return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
        }

        $userEmail = $data['email'] ?? null;
        $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$createdBy) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }

        $title = $data['title'];
        $participantsIds = $data['participants'];

        if (!in_array($createdBy->getId(), $participantsIds)) {
            $participantsIds[] = $createdBy->getId();
        }

        $participants = [];
        foreach ($participantsIds as $participantId) {
            $participant = $this->entityManager->getRepository(User::class)->find($participantId);
            if ($participant) {
                $participants[] = $participant;
            }
        }

        $existingConversation = $this->entityManager->getRepository(Conversation::class)->findOneByParticipants($participants);

        if ($existingConversation) {
            return new Response('Conversation already exists', Response::HTTP_CONFLICT);
        }

        $conversation = new Conversation();
        $conversation->setTitle($title);
        $conversation->setCreatedBy($createdBy);
        $conversation->setCreatedAt(new \DateTime());

        foreach ($participants as $participant) {
            $conversation->addParticipant($participant);
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Conversation created', 'conversationId' => $conversation->getId()], Response::HTTP_CREATED);
    }

    #[Route('/get-all/{id}', name: 'get_all_conversations', methods: ['GET'])]
    public function getConversationAll(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $conversations = $this->entityManager->getRepository(Conversation::class)
            ->findConversationsByUserOrderedByLastMessage($user);

        if (!$conversations) {
            return new JsonResponse(['conversations' => []], Response::HTTP_NOT_FOUND);
        }

        $conversationData = array_map(function ($conversation) {
            return [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                'lastMessageAt' => $conversation->getLastMessageAt(),
                'createdBy' => [
                    'id' => $conversation->getCreatedBy()->getId(),
                    'email' => $conversation->getCreatedBy()->getEmail(),
                    'userName' => $conversation->getCreatedBy()->getProfiles()->first() ? $conversation->getCreatedBy()->getProfiles()->first()->getUsername() : null,
                ],
                'participants' => array_map(function ($participant) {
                    return [
                        'id' => $participant->getId(),
                        'email' => $participant->getEmail(),
                        'userName' => $participant->getProfiles()->first() ? $participant->getProfiles()->first()->getUsername() : null,
                    ];
                }, $conversation->getParticipants()->toArray()),
                'isArchived' => $conversation->getIsArchived(),
                'isMutedUntil' => $conversation->getMutedUntil(),
            ];
        }, $conversations);

        return new JsonResponse(['conversations' => [$conversationData]], Response::HTTP_OK);
    }

    #[Route('/get-one/{id}', name: 'get_conversation_by_id', methods: ['GET'])]
    public function getConversationById(int $id): JsonResponse
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new JsonResponse(['message' => 'Conversation not found'], Response::HTTP_NOT_FOUND);
        }

        $conversationData = [
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
            'lastMessageAt' => $conversation->getLastMessageAt(),
            'createdBy' => [
                'id' => $conversation->getCreatedBy()->getId(),
                'email' => $conversation->getCreatedBy()->getEmail(),
                'userName' => $conversation->getCreatedBy()->getProfiles()->first() ? $conversation->getCreatedBy()->getProfiles()->first()->getUsername() : null,
            ],
            'participants' => array_map(function ($participant) {
                return [
                    'id' => $participant->getId(),
                    'email' => $participant->getEmail(),
                    'userName' => $participant->getProfiles()->first() ? $participant->getProfiles()->first()->getUsername() : null,
                ];
            }, $conversation->getParticipants()->toArray()),
            'isArchived' => $conversation->getIsArchived(),
            'isMutedUntil' => $conversation->getMutedUntil(),
        ];

        return new JsonResponse($conversationData);
    }

    #[Route('/add-participants/{id}', name: 'add_participants', methods: ['POST'])]
    public function addParticipantsById(int $id, Request $request): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['participant_ids']) || !is_array($data['participant_ids'])) {
            return new Response('Participant IDs not provided or invalid format', Response::HTTP_BAD_REQUEST);
        }

        $participantsAdded = [];
        $participantsAlreadyInConversation = [];
        foreach ($data['participant_ids'] as $participantId) {
            $participant = $this->entityManager->getRepository(User::class)->find($participantId);
            if (!$participant) {
                return new Response("User with ID {$participantId} not found", Response::HTTP_BAD_REQUEST);
            }
            if ($conversation->getParticipants()->contains($participant)) {
                $participantsAlreadyInConversation[] = $participant;
            } else {
                $conversation->addParticipant($participant);
                $participantsAdded[] = $participant;
            }
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $responseMessage = '';
        if (!empty($participantsAlreadyInConversation)) {
            $participantsIds = array_map(function ($participant) {
                return $participant->getId();
            }, $participantsAlreadyInConversation);
            $responseMessage .= 'Participants already in conversation: ' . implode(', ', $participantsIds) . '. ';
        }
        if (!empty($participantsAdded)) {
            $participantsIds = array_map(function ($participant) {
                return $participant->getId();
            }, $participantsAdded);
            $responseMessage .= 'Participants added to conversation: ' . implode(', ', $participantsIds);
        }

        return new Response($responseMessage, Response::HTTP_OK);
    }

    #[Route('/update-participant/{id}', name: 'update_participant', methods: ['PUT'])]
    public function updateParticipantById(int $id, Request $request): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['participants']) && is_array($data['participants'])) {
            $existingParticipants = $conversation->getParticipants()->toArray();
            $participantIds = array_map(function ($participant) {
                return $participant->getId();
            }, $existingParticipants);

            foreach ($data['participants'] as $participantId) {
                if (!in_array($participantId, $participantIds)) {
                    $participant = $this->entityManager->getRepository(User::class)->find($participantId);
                    if (!$participant) {
                        return new Response("User with ID {$participantId} not found", Response::HTTP_BAD_REQUEST);
                    }
                    $conversation->addParticipant($participant);
                }
            }

            foreach ($existingParticipants as $participant) {
                if (!in_array($participant->getId(), $data['participants'])) {
                    $conversation->removeParticipant($participant);
                }
            }
        }
        $conversation->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse(['success' => 'Conversation updated successfully']);
    }

    #[Route('/remove-participants/{id}', name: 'remove_participants', methods: ['DELETE'])]
    public function removeParticipantsById(int $id, Request $request): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['participant_ids']) || !is_array($data['participant_ids'])) {
            return new Response('Participant IDs not provided or invalid format', Response::HTTP_BAD_REQUEST);
        }

        $participantsRemoved = [];
        $participantsNotInConversation = [];
        foreach ($data['participant_ids'] as $participantId) {
            $participant = $this->entityManager->getRepository(User::class)->find($participantId);
            if (!$participant) {
                return new Response("User with ID {$participantId} not found", Response::HTTP_BAD_REQUEST);
            }
            if (!$conversation->getParticipants()->contains($participant)) {
                $participantsNotInConversation[] = $participant;
            } else {
                $conversation->removeParticipant($participant);
                $participantsRemoved[] = $participant;
            }
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $responseMessage = '';
        if (!empty($participantsNotInConversation)) {
            $participantsIds = array_map(function ($participant) {
                return $participant->getId();
            }, $participantsNotInConversation);
            $responseMessage .= 'Participants not in conversation: ' . implode(', ', $participantsIds) . '. ';
        }
        if (!empty($participantsRemoved)) {
            $participantsIds = array_map(function ($participant) {
                return $participant->getId();
            }, $participantsRemoved);
            $responseMessage .= 'Participants removed from conversation: ' . implode(', ', $participantsIds);
        }

        return new Response($responseMessage, Response::HTTP_OK);
    }


    #[Route('/delete/{id}', name: 'delete_conversation', methods: ['DELETE'])]
    public function deleteConversationById(int $id): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($conversation);
        $this->entityManager->flush();

        return new Response('Conversation deleted', Response::HTTP_OK);
    }

    #[Route('/archived/{userId}', name: 'get_archived_conversations_by_user_id', methods: ['GET'])]
    public function getArchivedConversationsByUserId(int $userId): Response
    {
        $conversations = $this->entityManager->getRepository(Conversation::class)->findArchivedConversationsByUserId($userId);

        if (empty($conversations)) {
            return new Response('No archived conversations found for this user', Response::HTTP_NOT_FOUND);
        }

        $conversationData = array_map(function ($conversation) {
            return [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                'createdBy' => [
                    'id' => $conversation->getCreatedBy()->getId(),
                    'email' => $conversation->getCreatedBy()->getEmail(),
                    'userName' => $conversation->getCreatedBy()->getProfiles()->first() ? $conversation->getCreatedBy()->getProfiles()->first()->getUsername() : null,
                ],
                'participants' => array_map(function ($participant) {
                    return [
                        'id' => $participant->getId(),
                        'email' => $participant->getEmail(),
                        'userName' => $participant->getProfiles()->first() ? $participant->getProfiles()->first()->getUsername() : null,
                    ];
                }, $conversation->getParticipants()->toArray()),
                'isArchived' => $conversation->getIsArchived(),
                'archivedAt' => $conversation->getArchivedAt() ? $conversation->getArchivedAt()->format('Y-m-d H:i:s') : null,
            ];
        }, $conversations);

        return new JsonResponse(['conversations' => [$conversationData]], Response::HTTP_OK);
    }

    #[Route('/archive/{conversationId}', name: 'archive_conversation', methods: ['POST'])]
    public function archiveConversation(int $conversationId): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $conversation->setIsArchived(true);
        $this->entityManager->flush();

        return new Response('Conversation archived', Response::HTTP_OK);
    }

    #[Route('/unarchive/{conversationId}', name: 'unarchive_conversation', methods: ['POST'])]
    public function unarchiveConversation(int $conversationId): Response
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);

        if (!$conversation) {
            return new Response('Conversation not found', Response::HTTP_NOT_FOUND);
        }

        $conversation->setIsArchived(false);
        $this->entityManager->flush();

        return new Response('Conversation unarchived', Response::HTTP_OK);
    }

    #[Route('/unarchive-all/{id}', name: 'unarchive_all_conversations', methods: ['POST'])]
    public function unarchiveAllConversations(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $conversations = $entityManager->getRepository(Conversation::class)->findBy([
            'isArchived' => true
        ]);

        if (!$conversations) {
            return new JsonResponse(['message' => 'Aucune conversations trouvées.'], Response::HTTP_NOT_FOUND);
        }

        foreach ($conversations as $conversation) {
            $conversation->setIsArchived(false);
            $entityManager->persist($conversation);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Toutes les conversations ont été désarchivées avec succès.']);
    }

    #[Route('/mute/{conversationId}', name: 'mute_conversation', methods: ['POST'])]
    public function muteConversation(int $conversationId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);

        if (!$conversation) {
            return new JsonResponse(['message' => 'Aucune conversation trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $duration = $data['duration'] ?? null;

        if (null === $duration) {
            return new JsonResponse(['message' => 'Durée de sourdine non spécifiée.'], Response::HTTP_BAD_REQUEST);
        }

        if ('eternal' === $duration) {
            $conversation->setMutedUntil(new \DateTime('9999-12-31 23:59:59'));
        } else {
            $timezone = new \DateTimeZone('Europe/Paris');
            $muteUntil = (new \DateTime('now', $timezone))->modify("+{$duration} hours");
            $conversation->setIsMuted(true);
            $conversation->setMutedUntil($muteUntil);
        }

        $entityManager->persist($conversation);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conversation mise en sourdine avec succès.']);
    }

    #[Route('/unmute/{conversationId}', name: 'unmute_conversation', methods: ['POST'])]
    public function unmuteConversation(int $conversationId, EntityManagerInterface $entityManager): JsonResponse
    {
        $conversation = $entityManager->getRepository(Conversation::class)->find($conversationId);

        if (!$conversation) {
            return new JsonResponse(['message' => 'Aucune conversation trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $conversation->setIsMuted(false);
        $conversation->setMutedUntil(null);

        $entityManager->persist($conversation);
        $entityManager->flush();

        return new JsonResponse(['message' => 'La sourdine de la conversation a été annulée avec succès.'], Response::HTTP_OK);
    }
}
