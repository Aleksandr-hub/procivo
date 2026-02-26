<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListProcessDefinitions;

use App\Workflow\Application\DTO\ProcessDefinitionDTO;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProcessDefinitionsHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $repository,
    ) {
    }

    /**
     * @return list<ProcessDefinitionDTO>
     */
    public function __invoke(ListProcessDefinitionsQuery $query): array
    {
        $status = null !== $query->status ? ProcessDefinitionStatus::from($query->status) : null;

        $definitions = $this->repository->findByOrganizationId($query->organizationId, $status);

        return array_map(
            static fn ($d) => ProcessDefinitionDTO::fromEntity($d),
            $definitions,
        );
    }
}
