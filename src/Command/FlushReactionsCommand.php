<?php

namespace App\Command;

use App\Service\Redis\RedisReactService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:flush-reactions',
    description: 'Flush reactions from Redis to PostgreSQL',
)]
class FlushReactionsCommand extends Command
{
    public function __construct(private readonly RedisReactService $redisReactService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redisReactService->flushAllReactionsToDatabase();
        $output->writeln('<info>Reactions flushed to database successfully!</info>');

        return Command::SUCCESS;
    }
}
