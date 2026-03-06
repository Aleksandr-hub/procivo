<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Infrastructure\Security\Service\PermissionResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Organization>
 */
final class OrganizationVoter extends Voter
{
    public const string VIEW = 'ORGANIZATION_VIEW';
    public const string MANAGE = 'ORGANIZATION_MANAGE';

    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly PermissionResolverInterface $permissionResolver,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE], true)
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return false;
        }

        /* @var Organization $subject */

        return match ($attribute) {
            self::VIEW => $this->canView($user, $subject),
            self::MANAGE => $this->canManage($user, $subject),
            default => false,
        };
    }

    private function canManage(SecurityUser $user, Organization $organization): bool
    {
        // Owner always has full access
        if ($organization->isOwner($user->getId())) {
            return true;
        }

        // Check if employee is active (prerequisite for any permission check)
        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $user->getId(),
            $organization->id(),
        );

        if (null === $employee || !$employee->isActive()) {
            return false;
        }

        // Delegate to PermissionResolver for hierarchical permission check
        // Manage action covers all organization management
        if ($this->permissionResolver->hasPermission(
            $user->getId(),
            $organization->id()->value(),
            PermissionResource::Organization,
            PermissionAction::Manage,
        )) {
            return true;
        }

        // Fall back to Update action for ORGANIZATION_MANAGE
        return $this->permissionResolver->hasPermission(
            $user->getId(),
            $organization->id()->value(),
            PermissionResource::Organization,
            PermissionAction::Update,
        );
    }

    private function canView(SecurityUser $user, Organization $organization): bool
    {
        // Owner always has full access
        if ($organization->isOwner($user->getId())) {
            return true;
        }

        // Check if employee is active (prerequisite for any permission check)
        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $user->getId(),
            $organization->id(),
        );

        if (null === $employee || !$employee->isActive()) {
            return false;
        }

        // Delegate to PermissionResolver for hierarchical permission check
        return $this->permissionResolver->hasPermission(
            $user->getId(),
            $organization->id()->value(),
            PermissionResource::Organization,
            PermissionAction::View,
        );
    }
}
