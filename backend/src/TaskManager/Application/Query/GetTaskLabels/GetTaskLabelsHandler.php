<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTaskLabels;

use App\TaskManager\Application\DTO\LabelDTO;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTaskLabelsHandler
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    /**
     * @return list<LabelDTO>
     */
    public function __invoke(GetTaskLabelsQuery $query): array
    {
        $labels = $this->labelRepository->findByTaskId(TaskId::fromString($query->taskId));

        return array_map(LabelDTO::fromEntity(...), $labels);
    }
}
