<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\DTO\Conversation\CreateConversationDTO;
use App\DTO\Conversation\MuteConversationDTO;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\ConversationService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/conversations')]
class ConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('', name: 'create_conversation', methods: ['POST'])]
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = new CreateConversationDTO($data);

            if (!isset($dto->participants) || !isset($dto->username)) {
                return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
            }

            $response = $this->conversationService->createConversation($dto);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}/all', name: 'get_all_conversations_with_last_messages', methods: ['GET'])]
    public function getAllConversationsByProfileIdWithLastMessages(int $profileId, Request $request): JsonResponse
    {
        try {
            $profile = $this->profileRepository->findOneBy(['id' => $profileId]);
            if (!$profile) {
                return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);

            $response = $this->conversationService->getAllConversationsWithLastMessages($profile, $page, $limit);

            return new JsonResponse(
                ['conversations' => $response['conversations'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}', name: 'get_conversation_by_id', methods: ['GET'])]
    public function getConversationById(int $conversationId): JsonResponse
    {
        try {
            $response = $this->conversationService->getOneConversationById($conversationId);

            return new JsonResponse(
                ['conversation' => $response['conversation'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}', name: 'delete_conversation', methods: ['DELETE'])]
    public function deleteConversationById(int $conversationId): JsonResponse
    {
        try {
            $response = $this->conversationService->deleteOneConversationById($conversationId);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}/archived', name: 'get_archived_conversations_by_user_id', methods: ['GET'])]
    public function getArchivedConversationsByUserId(int $profileId): JsonResponse
    {
        try {
            $response = $this->conversationService->getArchivedConversationsByUserId($profileId);

            return new JsonResponse(
                ['conversations' => $response['conversations'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}/archive', name: 'archive_conversation', methods: ['POST'])]
    public function archiveConversation(int $conversationId): JsonResponse
    {
        try {
            $response = $this->conversationService->archiveConversation($conversationId);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{conversationId}/unarchive', name: 'unarchive_conversation', methods: ['POST'])]
    public function unarchiveConversation(int $conversationId): JsonResponse
    {
        try {
            $response = $this->conversationService->unarchiveConversation($conversationId);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/unarchive/all', name: 'unarchive_all_conversations', methods: ['POST'])]
    public function unarchiveAllConversations(int $id): JsonResponse
    {
        try {
            $response = $this->conversationService->unarchiveAllConversations($id);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{conversationId}/mute', name: 'mute_conversation', methods: ['POST'])]
    public function muteConversation(int $conversationId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
            }

            $dto = new MuteConversationDTO($data);
            $response = $this->conversationService->muteConversation($conversationId, $dto);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? '', 'duration' => $response['duration'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{conversationId}/unmute', name: 'unmute_conversation', methods: ['POST'])]
    public function unmuteConversation(int $conversationId): JsonResponse
    {
        try {
            $response = $this->conversationService->unmuteConversation($conversationId);

            return new JsonResponse(
                ['message' => $response['message'] ?? '', 'error' => $response['error'] ?? ''],
                $response['status']
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
