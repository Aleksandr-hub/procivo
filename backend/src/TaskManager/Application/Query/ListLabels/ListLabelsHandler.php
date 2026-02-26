<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListLabels;

use App\TaskManager\Application\DTO\LabelDTO;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListLabelsHandler
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    /**
     * @return list<LabelDTO>
     */
    public function __invoke(ListLabelsQuery $query): array
    {
        $labels = $this->labelRepository->findByOrganizationId($query->organizationId);

        return array_map(LabelDTO::fromEntity(...), $labels);
    }
}
