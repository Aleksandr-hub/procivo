<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Console;

use App\Identity\Application\Command\PromoteUser\PromoteUserCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-user',
    description: 'Promote a user by assigning a role (default: ROLE_SUPER_ADMIN)',
)]
final class PromoteUserConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role to assign', 'ROLE_SUPER_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $role = $input->getOption('role');

        $this->commandBus->dispatch(new PromoteUserCommand(
            email: $email,
            role: $role,
        ));

        $io->success(\sprintf('Role "%s" assigned to user "%s".', $role, $email));

        return Command::SUCCESS;
    }
}
