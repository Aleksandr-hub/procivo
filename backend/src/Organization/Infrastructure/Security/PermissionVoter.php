<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Infrastructure\Security\Service\PermissionResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Organization>
 */
final class PermissionVoter extends Voter
{
    private const string PATTERN = '/^([A-Z]+)_([A-Z]+)$/';

    public function __construct(
        private readonly PermissionResolverInterface $permissionResolver,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return (bool) preg_match(self::PATTERN, $attribute)
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return false;
        }

        /** @var Organization $subject */

        if ($subject->isOwner($user->getId())) {
            return true;
        }

        preg_match(self::PATTERN, $attribute, $matches);
        $resource = PermissionResource::tryFrom(strtolower($matches[1]));
        $action = PermissionAction::tryFrom(strtolower($matches[2]));

        if (null === $resource || null === $action) {
            return false;
        }

        return $this->permissionResolver->hasPermission(
            $user->getId(),
            $subject->id()->value(),
            $resource,
            $action,
        );
    }
}
