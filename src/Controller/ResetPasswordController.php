<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\ResetPassword\ForgotPasswordRequestDTO;
use App\DTO\ResetPassword\ResetPasswordRequestDTO;
use App\Service\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/reset/password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly ResetPasswordService $resetPasswordService,
    ) {
    }

    #[Route('', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $resetToken = $data['token'] ?? null;
        $password = $data['password'] ?? null;

        $dto = new ResetPasswordRequestDTO(
            $data['token'],
            $data['password']
        );

        if (!$dto->token || !$dto->password) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->resetPasswordService->resetPassword($resetToken, $password);

        return new JsonResponse($result, $result['status'] ?? Response::HTTP_OK);
    }

    #[Route('/forgot', name: 'app_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, ResetPasswordService $passwordResetService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        $dto = new ForgotPasswordRequestDTO(
            $data['email'],
        );

        $response = $passwordResetService->requestPasswordReset($dto->email);

        if (isset($response['error'])) {
            return new JsonResponse(['error' => $response['error']], $response['status']);
        }

        $jsonResponse = new JsonResponse(['success' => true]);
        $jsonResponse->headers->set('X-Reset-Token', $response['resetToken']);

        return $jsonResponse;
    }
}
