<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Service;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Application\Port\CurrentUserProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class SecurityCurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getUserId(): string
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new \RuntimeException('No authenticated user.');
        }

        return $user->getId();
    }
}
