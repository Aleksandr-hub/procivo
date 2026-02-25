<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetPosition;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetPositionQuery implements QueryInterface
{
    public function __construct(
        public string $positionId,
    ) {
    }
}
