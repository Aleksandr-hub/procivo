<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
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
        if ($organization->isOwner($user->getId())) {
            return true;
        }

        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $user->getId(),
            $organization->id(),
        );

        return null !== $employee && $employee->isActive();
    }

    private function canView(SecurityUser $user, Organization $organization): bool
    {
        if ($organization->isOwner($user->getId())) {
            return true;
        }

        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $user->getId(),
            $organization->id(),
        );

        return null !== $employee && $employee->isActive();
    }
}
