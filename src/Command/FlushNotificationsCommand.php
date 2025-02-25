<?php

namespace App\Command;

use App\Message\FlushNotificationsMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:dispatch-flush-notifications',
    description: 'Dispatch a message to flush notifications from Redis to PostgreSQL',
)]
class FlushNotificationsCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bus->dispatch(new FlushNotificationsMessage());
        $output->writeln('<info>FlushNotificationsMessage dispatched successfully!</info>');

        return Command::SUCCESS;
    }
}
