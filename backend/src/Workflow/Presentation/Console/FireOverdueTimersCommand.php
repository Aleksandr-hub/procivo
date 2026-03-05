<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Console;

use App\Workflow\Infrastructure\Timer\FireTimerMessage;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:fire-overdue-timers', description: 'Fire overdue workflow timers (fallback poller)')]
final class FireOverdueTimersCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Run once and exit instead of looping');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $once = (bool) $input->getOption('once');

        do {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, process_instance_id, token_id, node_id FROM workflow_scheduled_timers WHERE fire_at <= NOW() AND fired_at IS NULL LIMIT 100',
            );

            $dispatched = 0;
            foreach ($rows as $row) {
                $this->connection->executeStatement(
                    'UPDATE workflow_scheduled_timers SET dispatched_at = NOW() WHERE id = ?',
                    [$row['id']],
                );

                $this->messageBus->dispatch(new FireTimerMessage(
                    processInstanceId: $row['process_instance_id'],
                    tokenId: $row['token_id'],
                    nodeId: $row['node_id'],
                ));

                ++$dispatched;
            }

            if ($dispatched > 0) {
                $output->writeln(sprintf('[%s] Dispatched %d overdue timer(s).', date('Y-m-d H:i:s'), $dispatched));
            }

            if (!$once) {
                sleep(300);
            }
        } while (!$once);

        return Command::SUCCESS;
    }
}
