<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Middleware;

use App\Shared\Application\Bus\EventBusInterface;
use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class DispatchDomainEventsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof AggregateRoot) {
                    foreach ($entity->pullDomainEvents() as $event) {
                        $this->eventBus->dispatch($event);
                    }
                }
            }
        }

        return $envelope;
    }
}
