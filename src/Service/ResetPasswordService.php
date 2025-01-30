<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\User;
use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

readonly class ResetPasswordService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenGeneratorInterface $tokenGenerator,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailService $mailService,
    ) {
    }

    public function requestPasswordReset(string $email): ?array
    {
        $userData = $this->userRepository->findOneUserByEmail($email);

        if (!$userData) {
            return ['error' => ErrorMessagesConstant::USER_NOT_FOUND, 'status' => JsonResponse::HTTP_NOT_FOUND];
        }

        $cooldownPeriod = new DateInterval('PT15M');
        $now = new DateTime();

        $lastRequest = $userData['lastPasswordResetRequest'] ? new DateTime($userData['lastPasswordResetRequest']) : null;
        $nextAllowedRequestTime = $lastRequest ? (clone $lastRequest)->add($cooldownPeriod) : null;

        if ($nextAllowedRequestTime && $now < $nextAllowedRequestTime) {
            return ['error' => 'You can only request a password reset once every 15 minutes.', 'status' => JsonResponse::HTTP_TOO_MANY_REQUESTS];
        }

        $resetToken = $this->tokenGenerator->generateToken();
        $tokenExpiration = (new DateTime())->modify('+1 hour');

        try {
            $this->updatePasswordResetToken($userData['id'], $resetToken, $tokenExpiration, $now);
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }

        $resetUrl = $_ENV['FRONT_URL'] . '/(auth)/reset-password?token=' . $resetToken;
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/reset_password_mail.html');
        $htmlContent = str_replace('{{ reset_url }}', $resetUrl, $htmlContent);

        try {
            $this->mailService->sendMail(
                $userData['email'],
                'Réinitialisation du mot de passe',
                $htmlContent,
                ['user' => $userData['email'], 'reset_url' => $resetUrl]
            );

            return ['success' => true, 'resetToken' => $resetToken];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    public function resetPassword(string $token, string $newPassword): ?array
    {
        $user = $this->userRepository->findOneByResetPwdToken($token);

        if (!$user) {
            return ['error' => ErrorMessagesConstant::INVALID_TOKEN, 'status' => Response::HTTP_NOT_FOUND];
        }

        if (new DateTime() > $user->getResetPwdTokenLifetime()) {
            return ['error' => ErrorMessagesConstant::TOKEN_EXPIRED, 'status' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

            $this->entityManager->createQueryBuilder()
                ->update(User::class, 'u')
                ->set('u.password', ':password')
                ->set('u.resetPwdToken', 'NULL')
                ->set('u.resetPwdTokenLifetime', 'NULL')
                ->where('u.id = :id')
                ->setParameter('password', $hashedPassword)
                ->setParameter('id', $user->getId())
                ->getQuery()
                ->execute();

            $htmlContent = file_get_contents(__DIR__ . '/../Emails/reset_password_confirmation_mail.html');
            $this->mailService->sendMail(
                $user->getEmail(),
                'Confirmation de réinitialisation du mot de passe',
                $htmlContent,
                ['user' => $user->getEmail()]
            );

            return ['success' => 'Password has been reset successfully'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    private function updatePasswordResetToken(int $userId, string $resetToken, DateTime $tokenExpiration, DateTime $lastRequest): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(User::class, 'u')
            ->set('u.lastPasswordResetRequest', ':lastRequest')
            ->set('u.resetPwdToken', ':resetToken')
            ->set('u.resetPwdTokenLifetime', ':tokenExpiration')
            ->where('u.id = :id')
            ->setParameter('lastRequest', $lastRequest)
            ->setParameter('resetToken', $resetToken)
            ->setParameter('tokenExpiration', $tokenExpiration)
            ->setParameter('id', $userId)
            ->getQuery()
            ->execute();
    }
}
