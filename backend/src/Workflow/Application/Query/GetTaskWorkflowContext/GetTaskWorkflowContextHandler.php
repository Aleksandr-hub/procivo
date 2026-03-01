<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetTaskWorkflowContext;

use App\Workflow\Application\DTO\TaskWorkflowContextDTO;
use App\Workflow\Application\Service\FormSchemaBuilder;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTaskWorkflowContextHandler
{
    public function __construct(
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private FormSchemaBuilder $formSchemaBuilder,
    ) {
    }

    public function __invoke(GetTaskWorkflowContextQuery $query): ?TaskWorkflowContextDTO
    {
        $link = $this->linkRepository->findByTaskId($query->taskId);
        if (null === $link) {
            return null;
        }

        $instance = $this->instanceRepository->findById(
            ProcessInstanceId::fromString($link->processInstanceId()),
        );
        if (null === $instance) {
            return null;
        }

        $definition = $this->definitionRepository->findById($instance->processDefinitionId());
        $processName = $definition?->name() ?? '';

        if ($link->isCompleted()) {
            return new TaskWorkflowContextDTO(
                processInstanceId: $link->processInstanceId(),
                processName: $processName,
                nodeName: '',
                nodeId: '',
                isCompleted: true,
                formSchema: ['shared_fields' => [], 'actions' => []],
            );
        }

        $version = $this->versionRepository->findById($instance->versionId());
        if (null === $version) {
            return null;
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $token = $instance->getToken(TokenId::fromString($link->tokenId()));
        $nodeId = $token->nodeId()->value();
        $nodeName = $graph->nodeName($nodeId);

        $formSchema = $this->formSchemaBuilder->build($graph, $nodeId);

        return new TaskWorkflowContextDTO(
            processInstanceId: $link->processInstanceId(),
            processName: $processName,
            nodeName: $nodeName,
            nodeId: $nodeId,
            isCompleted: false,
            formSchema: $formSchema,
        );
    }
}
