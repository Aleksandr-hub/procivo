<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CancelProcess;

use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CancelProcessHandler
{
    public function __construct(
        private ProcessInstanceRepositoryInterface $instanceRepository,
    ) {
    }

    public function __invoke(CancelProcessCommand $command): void
    {
        $instanceId = ProcessInstanceId::fromString($command->processInstanceId);
        $instance = $this->instanceRepository->findById($instanceId);

        if (null === $instance) {
            throw ProcessInstanceNotFoundException::withId($command->processInstanceId);
        }

        $instance->cancel($command->cancelledBy, $command->reason);

        $this->instanceRepository->save($instance);
    }
}
