<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Security;

use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class OrganizationAuthorizer
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private Security $security,
    ) {
    }

    public function authorize(string $organizationId, string $permission): void
    {
        $organization = $this->organizationRepository->findById(OrganizationId::fromString($organizationId));

        if (null === $organization) {
            throw new NotFoundHttpException(\sprintf('Organization "%s" not found.', $organizationId));
        }

        if (!$this->security->isGranted($permission, $organization)) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }
}
