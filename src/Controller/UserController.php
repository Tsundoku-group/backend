<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error';
    private const USER_NOT_FOUND = 'User not found';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository         $userRepository,
        private readonly MailService            $mailService
    )
    {
    }

    #[Route('/all', name: 'user_list', methods: ['GET'])]
    public function getAll(): Response
    {
        try {
            $users = $this->userRepository->findAll();
            $usernames = [];

            foreach ($users as $user) {
                foreach ($user->getProfiles() as $profile) {
                    $usernames[] = $profile->getUsername();
                }
            }

            return $this->json($usernames);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'user_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json($user, 201);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => self::USER_NOT_FOUND], 404);
        }

        try {
            $profilesData = [];

            foreach ($user->getProfiles() as $profile) {
                $profilesData[] = [
                    'id' => $user->getId(),
                    'username' => $profile->getUserName(),
                    'firstName' => $profile->getFirstName(),
                    'lastName' => $profile->getLastName(),
                    'birthDay' => $profile->getBirthday()?->format('Y-m-d'),
                    'email' => $user->getEmail(),
                    'biographie' => $profile->getBio(),
                ];
            }

            return $this->json($profilesData);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['PUT'])]
    public function update(Request $request, User $user): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $user->setEmail($data['email'] ?? $user->getEmail());
            $user->setPassword($data['password'] ?? $user->getPassword());

            $this->entityManager->flush();

            return $this->json($user, 200);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/verify-password', methods: ['POST'])]
    public function verifyPassword(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        try {
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                return new JsonResponse(['error' => 'Ancien mot de passe incorrect'], 400);
            }

            return $this->json($user, 200);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/update-password', methods: ['POST'])]
    public function updatePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $captchaToken = $data['captchaToken'];

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        if (empty($data['newPassword'])) {
            return new JsonResponse(['error' => 'Le nouveau mot de passe est requis'], 400);
        }

        if (empty($data['captchaToken'])) {
            return new JsonResponse(['error' => 'Le captcha est manquant'], 400);
        }

        if (!$this->verifyCaptcha($captchaToken)) {
            return $this->json(['message' => 'Captcha invalide.'], 400);
        }

        try {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            return $this->json($user, 200);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/delete-account-request/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function requestAccountDeletion(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if (null !== $user->getAccountDeletionDate()) {
            return new JsonResponse(['error' => 'Deletion already requested'], 400);
        }

        try {
            $deletionDate = new DateTime('+30 days');
            $user->setAccountDeletionDate($deletionDate);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->sendAccountDeletionEmail($user->getEmail());

            return new JsonResponse(['message' => 'Account deletion requested', 'deletionDate' => $deletionDate->format('Y-m-d')], 200);
        } catch (Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    private function sendAccountDeletionEmail(string $email): void
    {
        $subject = 'Demande de suppression de votre compte';
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/request_deletion_account_mail.html');
        $deletionDate = (new DateTime('+30 days'))->format('Y-m-d');
        $htmlContent = str_replace('{deletionDate}', $deletionDate, $htmlContent);

        try {
            $this->mailService->sendMail(
                $email,
                $subject,
                $htmlContent
            );
        } catch (Exception $e) {
            error_log('Erreur lors de l\'envoi de l\'email de suppression : ' . $e->getMessage());
        }
    }

    private function verifyCaptcha(string $captchaToken): bool
    {
        $secretKey = $_ENV['GOOGLE_RECAPTCHA_SECRET'];
        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $response = file_get_contents($url . '?secret=' . $secretKey . '&response=' . $captchaToken);
        $responseKeys = json_decode($response, true);

        return $responseKeys['success'] ?? false;
    }
}
