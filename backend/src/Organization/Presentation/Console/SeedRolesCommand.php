<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Console;

use App\Organization\Application\Command\SeedDefaultRoles\SeedDefaultRolesCommand as SeedDefaultRolesAppCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-roles',
    description: 'Seed default roles for an organization',
)]
final class SeedRolesCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('organizationId', InputArgument::REQUIRED, 'Organization ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orgId = $input->getArgument('organizationId');

        $this->commandBus->dispatch(new SeedDefaultRolesAppCommand($orgId));

        $io->success(\sprintf('Default roles seeded for organization %s', $orgId));

        return Command::SUCCESS;
    }
}
