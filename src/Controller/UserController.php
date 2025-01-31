<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\User\NewUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\DTO\User\VerifyPasswordDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Service\UserService;
use App\Validator\Constraints\CaptchaValidator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly MailService $mailService,
        private readonly CaptchaValidator $captchaValidator,
        private readonly UserService $userService,
    ) {
    }

    #[Route('/all', name: 'user_list', methods: ['GET'])]
    public function getAll(): Response
    {
        $users = $this->userRepository->findAllUsersByUsername();

        if (empty($users)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 404);
        }

        try {
            return $this->json($users);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'user_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $dto = new NewUserDTO(
                $data['email'] ?? '',
                $data['password'] ?? ''
            );

            $user = new User();
            $user->setEmail($dto->email);
            $user->setPassword($dto->password);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json($user, 201);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $user = $this->userRepository->findUserProfileById($id);

        if (empty($user)) {
            return $this->json(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 404);
        }

        try {
            return $this->json($user);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['PUT'])]
    public function update(Request $request, User $user): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $dto = new UpdateUserDTO(
                $data['email'] ?? null,
                $data['password'] ?? null
            );

            if ($dto->email) {
                $user->setEmail($dto->email);
            }

            if ($dto->password) {
                $user->setPassword($dto->password);
            }

            $this->entityManager->flush();

            return $this->json([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ], 200);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/password/verify', methods: ['POST'])]
    public function verifyPassword(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 404);
        }

        try {
            $dto = new VerifyPasswordDTO($data['currentPassword']);

            if (!$passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
                return new JsonResponse(['error' => 'Incorrect password'], 400);
            }

            return $this->json(['message' => 'Password verified'], 200);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/password/update', methods: ['POST'])]
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

        if (!$this->captchaValidator->verifyCaptcha($captchaToken)) {
            return $this->json(['message' => 'Captcha invalide.'], 400);
        }

        try {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            return $this->json($user, 200);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/delete/request', name: 'user_delete', methods: ['DELETE'])]
    public function requestAccountDeletion(int $id): JsonResponse
    {
        $user = $this->userRepository->findOneUserById($id);

        if (empty($user)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 404);
        }

        if (null !== $user['accountDeletionDate']) {
            return new JsonResponse(['error' => 'Deletion already requested'], 400);
        }

        try {
            $deletionDate = $this->userService->scheduleAccountDeletion($id);

            if (!$deletionDate) {
                return new JsonResponse(['error' => 'Account deletion could not be scheduled.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->sendAccountDeletionEmail($user['email']);

            return new JsonResponse(['message' => 'Account deletion requested', 'deletionDate' => $deletionDate->format('Y-m-d')], 200);
        } catch (Exception $e) {
            return $this->json(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
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
}
