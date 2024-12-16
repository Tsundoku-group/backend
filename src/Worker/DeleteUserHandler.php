<?php

namespace App\Worker;

use App\Message\DeleteUserMessage;
use App\Repository\UserRepository;
use App\Service\MailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteUserHandler
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private MailService $mailService;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, MailService $mailService)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->mailService = $mailService;
    }

    public function __invoke(DeleteUserMessage $message): void
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            if ($user->getAccountDeletionDate() < new DateTime()) {
                $email = $user->getEmail();

                $this->entityManager->remove($user);

                $this->sendAccountDeletionEmail($email);
            }
        }

        $this->entityManager->flush();
    }

    private function sendAccountDeletionEmail(string $email): void
    {
        $htmlContent = file_get_contents(__DIR__ . '/../Emails/deletion_account_mail.html');

        try {
            $this->mailService->sendMail(
                $email,
                'Suppression de votre compte utilisateur',
                $htmlContent
            );
        } catch (RuntimeException $e) {
            error_log('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }
}
