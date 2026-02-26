<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Shared\Application\Bus\EventBusInterface;
use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Infrastructure\EventStore\EventSerializer;
use App\Workflow\Infrastructure\EventStore\EventStoreInterface;

final readonly class EventSourcedProcessInstanceRepository implements ProcessInstanceRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private EventBusInterface $eventBus,
        private EventSerializer $serializer,
    ) {
    }

    public function save(ProcessInstance $processInstance): void
    {
        $uncommittedEvents = $processInstance->uncommittedEvents();
        $currentVersion = $processInstance->version();

        foreach ($uncommittedEvents as $i => $event) {
            $this->eventStore->append(
                $processInstance->id()->value(),
                $event,
                $currentVersion + $i + 1,
            );
        }

        // Dispatch events to event bus for side effects (projections, integrations)
        foreach ($uncommittedEvents as $event) {
            $this->eventBus->dispatch($event);
        }

        $processInstance->clearUncommittedEvents();
    }

    public function findById(ProcessInstanceId $id): ?ProcessInstance
    {
        $storedEvents = $this->eventStore->load($id->value());

        if (0 === \count($storedEvents)) {
            return null;
        }

        $domainEvents = array_map(
            fn ($stored) => $this->serializer->deserialize(
                $stored->eventType,
                $stored->payload,
                $stored->occurredAt,
            ),
            $storedEvents,
        );

        return ProcessInstance::reconstitute($domainEvents);
    }
}
