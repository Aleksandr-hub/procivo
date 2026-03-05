<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Port;

interface UserQueryPort
{
    /**
     * @param list<string> $userIds
     *
     * @return array<string, string> Map of userId => displayName
     */
    public function resolveDisplayNames(array $userIds): array;
}
