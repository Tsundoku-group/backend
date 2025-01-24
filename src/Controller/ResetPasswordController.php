<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailService $mailService,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    )
    {
    }
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $cooldownPeriod = new DateInterval('PT15M');
        $now = new DateTime('now');

        $lastRequest = $user->getLastPasswordResetRequest();

        if ($lastRequest instanceof DateTime) {
            $nextAllowedRequestTime = (clone $lastRequest)->add($cooldownPeriod);
        } else {
            $nextAllowedRequestTime = null;
        }

        if ($nextAllowedRequestTime && $now < $nextAllowedRequestTime) {
            return new JsonResponse(['error' => 'You can only request a password reset once every 15 minutes.'], JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        $user->setLastPasswordResetRequest($now);

        $resetToken = $this->tokenGenerator->generateToken();
        $user->setResetPwdToken($resetToken);
        $user->setResetPwdTokenLifetime((new DateTime())->modify('+1 hour'));
        $this->entityManager->flush();

        $resetUrl = $_ENV['FRONT_URL'] . '/(auth)/reset-password?token=';
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/reset_password_mail.html');
        $htmlContent = str_replace('{{ reset_url }}', $resetUrl, $htmlContent);

        try {
            $this->mailService->sendMail(
                $user->getEmail(),
                'Réinitialisation du mot de passe',
                $htmlContent,
                ['user' => $user->getEmail(), 'reset_url' => $resetUrl]
            );

            $response = new JsonResponse(['success' => true]);
            $response->headers->set('X-Reset-Token', $resetToken);

            return $response;
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $resetToken = $data['token'] ?? null;

        if (!$resetToken) {
            return new JsonResponse(['error' => 'Token not found'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetPwdToken' => $resetToken]);
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_NOT_FOUND);
        }

        if (new DateTime() > $user->getResetPwdTokenLifetime()) {
            return new JsonResponse(['error' => 'Token expired'], Response::HTTP_BAD_REQUEST);
        }

        if (!$data || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $user->setResetPwdToken(null);
        $user->setResetPwdTokenLifetime(null);

        $this->entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/reset_password_confirmation_mail.html');
        try {
            $this->mailService->sendMail(
                $user->getEmail(),
                'Confirmation de réinitialisation du mot de passe',
                $htmlContent,
                ['user' => $user->getEmail()]
            );
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => 'Password has been reset successfully']);
    }
}
