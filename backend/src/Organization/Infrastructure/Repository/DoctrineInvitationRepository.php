<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Domain\ValueObject\InvitationStatus;
use App\Organization\Domain\ValueObject\InvitationToken;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineInvitationRepository implements InvitationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Invitation $invitation): void
    {
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
    }

    public function findById(InvitationId $id): ?Invitation
    {
        return $this->entityManager->find(Invitation::class, $id->value());
    }

    public function findByToken(InvitationToken $token): ?Invitation
    {
        return $this->entityManager->getRepository(Invitation::class)->findOneBy([
            'token' => $token->value(),
        ]);
    }

    public function findPendingByEmailAndOrganization(Email $email, OrganizationId $organizationId): ?Invitation
    {
        return $this->entityManager->getRepository(Invitation::class)->findOneBy([
            'email' => $email->value(),
            'organizationId' => $organizationId->value(),
            'status' => InvitationStatus::Pending->value,
        ]);
    }

    /**
     * @return list<Invitation>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Invitation::class)->findBy(
            ['organizationId' => $organizationId->value()],
            ['createdAt' => 'DESC'],
        );
    }
}
