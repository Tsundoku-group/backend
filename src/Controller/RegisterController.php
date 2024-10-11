<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegisterController extends AbstractController
{

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request                     $request,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailService                 $mailService,
        TokenGeneratorInterface     $tokenGenerator,
        UrlGeneratorInterface       $urlGenerator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already in use'], JsonResponse::HTTP_CONFLICT);
        }

        $tokenRegistration = $tokenGenerator->generateToken();

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setTokenRegistration($tokenRegistration);

        $entityManager->persist($user);
        $entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/confirm_mail.html');
        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $tokenRegistration;
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, $htmlContent);

        try {
            // Send mail
            $mailService->sendMail(
                $user->getEmail(),
                'Confirmation du compte utilisateur',
                $htmlContent,
                [
                    'user' => $user->getEmail(),
                    'token' => $tokenRegistration,
                    'LifeTimeToken' => $user->getTokenRegistrationLifetime()->format('d-m-Y H:i:s')
                ]
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/confirm', name: 'app_confirm', methods: ['GET'])]
    public function confirm(Request $request, EntityManagerInterface $entityManager, MailService $mailService): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Invalid token'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['tokenRegistration' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setTokenRegistration(null);
        $user->setVerified(true);
        $entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/isActive_mail.html');

        try {
            $mailService->sendMail(
                $user->getEmail(),
                'Activation du compte réussie',
                $htmlContent,
                [
                    'user' => $user->getEmail(),
                ]
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => 'Account confirmed']);
    }

    #[Route('/resend-confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resendConfirmationEmail(
        Request                 $request,
        EntityManagerInterface  $entityManager,
        MailService             $mailService,
        TokenGeneratorInterface $tokenGenerator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['error' => 'User is already verified'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $tokenRegistration = $tokenGenerator->generateToken();
        $user->setTokenRegistration($tokenRegistration);
        $user->setTokenRegistrationLifetime((new \DateTime('now'))->add(new \DateInterval('P1D'))); // token lifetime 1 day
        $entityManager->flush();

        $htmlContent = file_get_contents(__DIR__ . '/../Emails/confirm_mail.html');
        $confirmationUrl = $_ENV['APP_URL'] . '/confirm?token=' . $tokenRegistration;
        $htmlContent = str_replace('{{ confirmation_url }}', $confirmationUrl, $htmlContent);

        try {
            // Send mail
            $mailService->sendMail(
                $user->getEmail(),
                'Confirmation du compte utilisateur',
                $htmlContent,
                [
                    'user' => $user->getEmail(),
                    'token' => $tokenRegistration,
                    'LifeTimeToken' => $user->getTokenRegistrationLifetime()->format('d-m-Y H:i:s')
                ]
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => 'Confirmation email resent successfully']);
    }
}