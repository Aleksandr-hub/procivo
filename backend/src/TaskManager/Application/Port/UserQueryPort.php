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

    /**
     * @param list<string> $userIds
     *
     * @return array<string, array{name: string, avatarUrl: string|null}> Map of userId => {name, avatarUrl}
     */
    public function resolveDisplayNamesWithAvatars(array $userIds): array;
}
