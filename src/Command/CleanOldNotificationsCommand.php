<?php

namespace App\Command;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clean-old-notifications')]
class CleanOldNotificationsCommand extends Command
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateLimit = new DateTimeImmutable('-30 days');
        $oldNotifications = $this->notificationRepository->findOlderThan($dateLimit);

        if (empty($oldNotifications)) {
            $this->logger->info('ℹ️ Aucune notification à supprimer.');
            return Command::SUCCESS;
        }

        foreach ($oldNotifications as $notification) {
            $this->entityManager->remove($notification);
        }

        $this->entityManager->flush();

        $this->logger->info("✅ Supprimé " . count($oldNotifications) . " anciennes notifications.");
        return Command::SUCCESS;
    }
}