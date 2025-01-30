<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Message\GetMessageDTO;
use App\DTO\Message\MarkMessageReadDTO;
use App\DTO\Message\SendMessageDTO;
use App\Entity\User;
use App\Service\MessageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/message')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {
    }

    #[Route('/{conversationId}/send', name: 'send_message', methods: ['POST'])]
    public function sendMessage(int $conversationId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new SendMessageDTO($data);
        try {
            $response = $this->messageService->sendMessage($conversationId, (array) $dto);

            if (isset($response['error'])) {
                return new JsonResponse(['error' => $response['error']], $response['status']);
            }

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{conversationId}', name: 'get_messages', methods: ['GET'])]
    public function getMessages(int $conversationId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $queryParams = $request->query->all();
        $dto = new GetMessageDTO($queryParams);
        $response = $this->messageService->getMessages($conversationId, (array) $dto, $user);

        if (isset($response['error'])) {
            return new JsonResponse(['error' => $response['error']], $response['status']);
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    #[Route('/{conversationId}/mark/read', name: 'mark_messages_read', methods: ['POST'])]
    public function markMessagesRead(int $conversationId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userEmail'])) {
            return new JsonResponse(['error' => 'User email is required.'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new MarkMessageReadDTO($data);

        $response = $this->messageService->markMessagesRead($conversationId, $dto->userEmail);

        return new JsonResponse($response, $response['status'] ?? Response::HTTP_OK);
    }
}
