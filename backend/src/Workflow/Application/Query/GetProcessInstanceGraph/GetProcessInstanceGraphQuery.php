<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessInstanceGraph;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetProcessInstanceGraphQuery implements QueryInterface
{
    public function __construct(
        public string $instanceId,
    ) {
    }
}
