<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\ExecuteTaskAction;

use App\Shared\Application\Bus\CommandBusInterface;
use App\TaskManager\Application\Command\TransitionTask\TransitionTaskCommand;
use App\Workflow\Domain\Exception\FormValidationException;
use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ExecuteTaskActionHandler
{
    public function __construct(
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private WorkflowEngine $engine,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ExecuteTaskActionCommand $command): void
    {
        $link = $this->linkRepository->findByTaskId($command->taskId);

        if (null === $link) {
            throw WorkflowExecutionException::invalidTransition(\sprintf('No workflow link found for task "%s"', $command->taskId));
        }

        if ($link->isCompleted()) {
            throw WorkflowExecutionException::invalidTransition(\sprintf('Task "%s" action already executed', $command->taskId));
        }

        $instanceId = ProcessInstanceId::fromString($link->processInstanceId());
        $instance = $this->instanceRepository->findById($instanceId);

        if (null === $instance) {
            throw ProcessInstanceNotFoundException::withId($link->processInstanceId());
        }

        if (!$instance->isRunning()) {
            throw WorkflowExecutionException::processNotRunning($link->processInstanceId());
        }

        $version = $this->versionRepository->findById($instance->versionId());
        if (null === $version) {
            throw WorkflowExecutionException::invalidTransition('Process definition version not found');
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $tokenId = TokenId::fromString($link->tokenId());
        $token = $instance->getToken($tokenId);
        $nodeId = $token->nodeId()->value();

        $transition = $graph->findOutgoingTransitionByActionKey($nodeId, $command->actionKey);
        if (null === $transition) {
            $outgoing = $graph->outgoingTransitions($nodeId);
            if (1 === \count($outgoing)) {
                $transition = $outgoing[0];
            }
        }

        if (null !== $transition) {
            $this->validateFormData($transition, $command->formData);
        }

        if ([] !== $command->formData) {
            $instance->mergeVariables($nodeId, $command->actionKey, $command->formData);
        }

        $this->engine->executeAction($instance, $tokenId, $graph, $command->actionKey);
        $this->instanceRepository->save($instance);

        $link->markCompleted();
        $this->linkRepository->save($link);

        $this->commandBus->dispatch(new TransitionTaskCommand(
            taskId: $command->taskId,
            transition: 'workflow_complete',
        ));
    }

    /**
     * @param array<string, mixed> $transition
     * @param array<string, mixed> $formData
     */
    private function validateFormData(array $transition, array $formData): void
    {
        /** @var list<array<string, mixed>> $formFields */
        $formFields = $transition['form_fields'] ?? [];

        $missingFields = [];
        foreach ($formFields as $field) {
            $isRequired = (bool) ($field['required'] ?? false);
            $fieldKey = \is_string($field['name'] ?? null) ? $field['name'] : '';

            if ($isRequired && '' !== $fieldKey) {
                $value = $formData[$fieldKey] ?? null;
                if (null === $value || '' === $value) {
                    $missingFields[] = $fieldKey;
                }
            }
        }

        if ([] !== $missingFields) {
            throw FormValidationException::requiredFieldsMissing($missingFields);
        }
    }
}
