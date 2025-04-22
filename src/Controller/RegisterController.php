<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Register\RegisterUserDTO;
use App\Service\RegisterService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/register')]
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterService $registerService,
    ) {
    }

    #[Route('', name: 'app_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }
        $dto = new RegisterUserDTO($data['email'], $data['password']);

        try {
            $response = $this->registerService->registerUser($dto->email, $dto->password);

            return new JsonResponse($response, $response['status'] ?? Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/confirm', name: 'app_confirm', methods: ['GET'])]
    public function confirm(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_TOKEN], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->registerService->confirmUser($token);

            return new JsonResponse(['success' => 'Compte confirmé'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/resend/confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resendConfirmationEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->registerService->resendConfirmationEmail($data['email']);

            return new JsonResponse(['success' => 'Courriel de confirmation envoyé avec succès'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
