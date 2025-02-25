<?php

namespace App\Command;

use App\Service\Redis\RedisNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use  Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:flush-notifications',
    description: 'Flush notifications from Redis to PostgreSQL'
)]
class FlushNotificationsCommand extends Command
{
    public function __construct(private readonly RedisNotificationService $redisNotificationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redisNotificationService->flushNotificationsToDatabase();
        $output->writeln('<info>Flushing notifications from Redis to PostgreSQL</info>');
        return Command::SUCCESS;
    }
}