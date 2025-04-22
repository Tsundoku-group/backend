<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\DTO\Conversation\CreateConversationDTO;
use App\Entity\Conversation;
use App\Entity\Profile;
use App\Repository\ConversationRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\Redis\RedisMessageService;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class ConversationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly RedisMessageService $redisMessageService,
    ) {
    }

    public function createConversation(CreateConversationDTO $dto): array
    {
        $createdBy = $this->profileRepository->findProfileByEmail($dto->email);
        if (!$createdBy) {
            return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
        }

        try {
            $participantsIds = $dto->participants;
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

            if ($this->conversationRepository->findOneByParticipants($participants)) {
                return ['error' => 'La conversation existe déjà', 'status' => 409];
            }

            $conversation = new Conversation();
            $conversation->setCreatedBy($createdBy);
            $conversation->setCreatedAt(new DateTimeImmutable());

            foreach ($participants as $participant) {
                $conversation->addParticipant($participant);
            }

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return ['message' => 'Conversation créée', 'conversationId' => $conversation->getId(), 'status' => 201];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function getAllConversationsWithLastMessages($user, int $page, int $limit): array
    {
        try {
            $conversations = $this->conversationRepository->findConversationsByUserOrderedByLastMessage($user, $page, $limit);
            if (!$conversations) {
                return ['conversations' => [], 'status' => Response::HTTP_OK];
            }

            $lastMessages = [];
            foreach ($conversations as $conversation) {
                $messages = $this->redisMessageService->getMessagesFromConversation($conversation->getId());
                $lastMessages[$conversation->getId()] = $messages ? end($messages) : null;
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

            $conversationData = array_filter(array_map(function ($conversation) use ($lastMessages) {
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
                    }, $conversation->getParticipants() ? $conversation->getParticipants()->toArray() : []),
                    'isArchived' => $conversation->getIsArchived(),
                    'isMutedUntil' => $conversation->getMutedUntil(),
                ] : null;
            }, $limitedConversations));

            return ['conversations' => $conversationData, 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function getOneConversationById(int $id): array
    {
        try {
            $conversation = $this->conversationRepository->find($id);

            if (!$conversation) {
                return ['error' => 'Conversation introuvable', 'status' => Response::HTTP_NOT_FOUND];
            }

            $createdBy = $conversation->getCreatedBy();
            if (!$createdBy || !$createdBy->getUser()) {
                return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => Response::HTTP_NOT_FOUND];
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
                    if (!$participant->getUser()) {
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

            return ['conversation' => $conversationData, 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function deleteOneConversationById(int $id): array
    {
        try {
            $conversation = $this->conversationRepository->find($id);

            if (!$conversation) {
                return ['error' => 'Conversation introuvable', 'status' => Response::HTTP_NOT_FOUND];
            }

            $this->entityManager->remove($conversation);
            $this->entityManager->flush();

            return ['message' => 'Conversation supprimée', 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function getArchivedConversationsByUserId(int $userId): array
    {
        try {
            $conversations = $this->conversationRepository->findArchivedConversationsByUserId($userId);

            if (empty($conversations)) {
                return ['error' => "Aucune conversation archivée n'a été trouvée pour cet utilisateur", 'status' => Response::HTTP_NOT_FOUND];
            }

            $lastMessages = [];
            foreach ($conversations as $conversation) {
                $messages = $this->redisMessageService->getMessagesFromConversation($conversation->getId());
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

            return ['conversations' => $conversationData, 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function archiveConversation(int $conversationId): array
    {
        try {
            $conversation = $this->conversationRepository->find($conversationId);

            if (!$conversation) {
                return ['error' => 'Conversation introuvable', 'status' => Response::HTTP_NOT_FOUND];
            }

            if ($conversation->getIsArchived()) {
                return ['error' => 'La conversation est déjà archivée', 'status' => Response::HTTP_FORBIDDEN];
            }

            $conversation->setIsArchived(true);
            $this->entityManager->flush();

            return ['message' => 'Conversation archivée', 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function unarchiveConversation(int $conversationId): array
    {
        try {
            $conversation = $this->conversationRepository->find($conversationId);

            if (!$conversation) {
                return ['error' => 'Conversation introuvable', 'status' => Response::HTTP_NOT_FOUND];
            }

            if (!$conversation->getIsArchived()) {
                return ['error' => 'La conversation est déjà désarchivée', 'status' => Response::HTTP_CONFLICT];
            }

            $conversation->setIsArchived(false);
            $this->entityManager->flush();

            return ['message' => 'Conversation non archivée', 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function unarchiveAllConversations(int $userId): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => Response::HTTP_NOT_FOUND];
            }

            $conversations = $this->conversationRepository->findBy(['isArchived' => true]);

            if (empty($conversations)) {
                return ['error' => 'Aucune conversation trouvée.', 'status' => Response::HTTP_NOT_FOUND];
            }

            foreach ($conversations as $conversation) {
                $conversation->setIsArchived(false);
                $this->entityManager->persist($conversation);
            }

            $this->entityManager->flush();

            return ['message' => 'Toutes les conversations ont été désarchivées avec succès.', 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function muteConversation(int $conversationId, $dto): array
    {
        try {
            $conversation = $this->conversationRepository->find($conversationId);
            if (!$conversation) {
                return ['error' => 'Aucune conversation trouvée.', 'status' => Response::HTTP_NOT_FOUND];
            }

            $duration = $dto->duration;
            if (!$duration) {
                return ['error' => 'Durée de sourdine non spécifiée.', 'status' => Response::HTTP_BAD_REQUEST];
            }

            $muteUntil = null;

            if ('eternal' === $duration) {
                $conversation->setMutedUntil(new DateTime('9999-12-31 23:59:59'));
            } else {
                $timezone = new DateTimeZone('Europe/Paris');
                $muteUntil = (new DateTime('now', $timezone))->modify("+{$duration} hours");

                if (!$muteUntil) {
                    return ['error' => 'Durée invalide.', 'status' => Response::HTTP_BAD_REQUEST];
                }

                $conversation->setIsMuted(true);
                $conversation->setMutedUntil($muteUntil);
            }

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return ['duration' => $muteUntil->format('Y-m-d H:i:s'), 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function unmuteConversation(int $conversationId): array
    {
        try {
            $conversation = $this->conversationRepository->find($conversationId);

            if (!$conversation) {
                return ['error' => 'Aucune conversation trouvée.', 'status' => Response::HTTP_NOT_FOUND];
            }

            $conversation->setIsMuted(false);
            $conversation->setMutedUntil(null);

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            return ['message' => 'La sourdine de la conversation a été annulée avec succès.', 'status' => Response::HTTP_OK];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
    }
}
