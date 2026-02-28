<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessInstanceGraph;

use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessInstanceGraphHandler
{
    public function __construct(
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
    ) {
    }

    /**
     * @return array{nodes: list<array<string, mixed>>, transitions: list<array<string, mixed>>}|null
     */
    public function __invoke(GetProcessInstanceGraphQuery $query): ?array
    {
        $instance = $this->instanceRepository->findById(
            ProcessInstanceId::fromString($query->instanceId),
        );

        if (null === $instance) {
            return null;
        }

        $version = $this->versionRepository->findById($instance->versionId());

        if (null === $version) {
            return null;
        }

        return $version->nodesSnapshot();
    }
}
