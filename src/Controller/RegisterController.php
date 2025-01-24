<?php

namespace App\Controller;

use App\DTO\Register\RegisterUserDTO;
use App\DTO\Register\ResendConfirmationEmailDTO;
use App\Entity\User;
use App\Service\MailService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly MailService $mailService,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new RegisterUserDTO(
            $data['email'],
            $data['password']
        );

        $user = new User();
        $user->setEmail($dto->email);

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already in use'], JsonResponse::HTTP_CONFLICT);
        }

        $tokenRegistration = $this->tokenGenerator->generateToken();

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setTokenRegistration($tokenRegistration);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/confirm_mail.html');
        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $tokenRegistration;
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, $htmlContent);

        try {
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
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/confirm', name: 'app_confirm', methods: ['GET'])]
    public function confirm(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Invalid token'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['tokenRegistration' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setTokenRegistration(null);
        $user->setVerified(true);
        $this->entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/isActive_mail.html');

        try {
            $this->mailService->sendMail(
                $user->getEmail(),
                'Activation du compte réussie',
                $htmlContent,
                [
                    'user' => $user->getEmail(),
                ]
            );
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => 'Account confirmed']);
    }

    #[Route('/resend-confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resendConfirmationEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new ResendConfirmationEmailDTO(
            $data['email'],
        );

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['error' => 'User is already verified'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $tokenRegistration = $this->tokenGenerator->generateToken();
        $user->setTokenRegistration($tokenRegistration);
        $user->setTokenRegistrationLifetime((new DateTime('now'))->add(new DateInterval('P1D'))); // token lifetime 1 day
        $this->entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/confirm_mail.html');
        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $tokenRegistration;
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, $htmlContent);

        try {
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
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => 'Confirmation email resent successfully']);
    }
}
