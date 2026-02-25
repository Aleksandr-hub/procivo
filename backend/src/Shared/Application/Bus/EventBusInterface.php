<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

use App\Shared\Domain\DomainEvent;

interface EventBusInterface
{
    public function dispatch(DomainEvent $event): void;
}
