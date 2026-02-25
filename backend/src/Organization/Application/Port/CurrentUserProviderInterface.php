<?php

declare(strict_types=1);

namespace App\Organization\Application\Port;

interface CurrentUserProviderInterface
{
    public function getUserId(): string;
}
