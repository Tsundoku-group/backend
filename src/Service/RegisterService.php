<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\Entity\User;
use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

readonly class RegisterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenGeneratorInterface $tokenGenerator,
        private MailService $mailService,
    ) {
    }

    public function registerUser(string $email, string $password): array
    {
        $existingUser = $this->userRepository->findOneUserByEmail($email);
        if ($existingUser) {
            return ['error' => UserErrorMessagesConstant::EMAIL_ALREADY_IN_USE, 'status' => 409];
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setTokenRegistration($this->tokenGenerator->generateToken());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendConfirmationEmail($user);

        return ['success' => true];
    }

    public function confirmUser(string $token): void
    {
        $user = $this->userRepository->findOneByRegistrationToken($token);
        if (!$user) {
            throw new Exception(UserErrorMessagesConstant::USER_NOT_FOUND, 404);
        }

        $user->setTokenRegistration(null);
        $user->setVerified(true);
        $this->entityManager->flush();

        $this->sendActivationEmail($user);
    }

    public function resendConfirmationEmail(string $email): void
    {
        $user = $this->userRepository->findOneUserByEmailAndIsVerified($email);
        if (!$user) {
            throw new Exception(UserErrorMessagesConstant::USER_NOT_FOUND, 404);
        }

        $tokenRegistration = $this->tokenGenerator->generateToken();
        $user->setTokenRegistration($tokenRegistration);
        $user->setTokenRegistrationLifetime((new DateTime('now'))->add(new DateInterval('P1D')));

        $this->entityManager->flush();

        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $tokenRegistration;
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/confirm_mail.html');
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, $htmlContent);

        $this->mailService->sendMail(
            $user->getEmail(),
            'Confirmation du compte utilisateur',
            $htmlContent,
            [
                'user' => $user->getEmail(),
                'token' => $tokenRegistration,
                'LifeTimeToken' => $user->getTokenRegistrationLifetime()->format('d-m-Y H:i:s'),
            ]
        );
    }

    private function sendConfirmationEmail(User $user): void
    {
        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $user->getTokenRegistration();
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, file_get_contents(__DIR__ . '/../Emails/confirm_mail.html'));

        $this->mailService->sendMail(
            $user->getEmail(),
            'Confirmation du compte utilisateur',
            $htmlContent,
            [
                'user' => $user->getEmail(),
                'token' => $user->getTokenRegistration(),
                'LifeTimeToken' => $user->getTokenRegistrationLifetime()?->format('d-m-Y H:i:s'),
            ]
        );
    }

    private function sendActivationEmail(User $user): void
    {
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/isActive_mail.html');

        $this->mailService->sendMail(
            $user->getEmail(),
            'Activation du compte réussie',
            $htmlContent,
            [
                'user' => $user->getEmail(),
            ]
        );
    }
}
