<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\DTO\Message\GetMessageDTO;
use App\DTO\Message\MarkMessageReadDTO;
use App\DTO\Message\SendMessageDTO;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Service\MessageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/messages')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly ProfileRepository $profileRepository
    )
    {
    }

    #[Route('/{conversationId}/send', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $conversationId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new SendMessageDTO($data);
        try {
            $response = $this->messageService->sendMessage($conversationId, (array)$dto);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}', name: 'get_messages', methods: ['GET'])]
    public function getMessages(int $conversationId, Request $request): JsonResponse
    {
        $profileId = $request->query->get('profileId');
        if (!$profileId) {
            return new JsonResponse(['error' => 'ID manquant.'], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $queryParams = $request->query->all();
        $dto = new GetMessageDTO($queryParams);
        try {
            $response = $this->messageService->getMessages($conversationId, (array)$dto, $profile);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}/mark/read', name: 'mark_messages_read', methods: ['POST'])]
    public function markMessagesRead(int $conversationId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userEmail'])) {
            return new JsonResponse(['error' => "L'adresse électronique de l'utilisateur est requise."], Response::HTTP_BAD_REQUEST);
        }

        $dto = new MarkMessageReadDTO($data);

        $response = $this->messageService->markMessagesRead($conversationId, $dto->userEmail);

        return new JsonResponse($response, $response['status'] ?? Response::HTTP_OK);
    }
}
