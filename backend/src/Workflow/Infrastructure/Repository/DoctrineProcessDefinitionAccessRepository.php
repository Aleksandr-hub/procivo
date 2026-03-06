<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\ProcessDefinitionAccess;
use App\Workflow\Domain\Repository\ProcessDefinitionAccessRepositoryInterface;
use App\Workflow\Domain\ValueObject\AccessType;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProcessDefinitionAccessRepository implements ProcessDefinitionAccessRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
    ) {
    }

    public function findByProcessDefinitionId(string $processDefinitionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(ProcessDefinitionAccess::class, 'a')
            ->where('a.processDefinitionId = :defId')
            ->setParameter('defId', $processDefinitionId)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProcessDefinitionIdAndType(string $processDefinitionId, AccessType $type): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(ProcessDefinitionAccess::class, 'a')
            ->where('a.processDefinitionId = :defId')
            ->andWhere('a.accessType = :type')
            ->setParameter('defId', $processDefinitionId)
            ->setParameter('type', $type->value)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAccessibleDefinitionIds(
        string $organizationId,
        ?string $departmentId,
        array $roleIds,
        AccessType $type,
    ): array {
        // Definitions in this org that have NO access rows for the given type (unrestricted)
        $unrestricted = <<<'SQL'
            SELECT pd.id
            FROM workflow_process_definitions pd
            WHERE pd.organization_id = :orgId
              AND NOT EXISTS (
                  SELECT 1 FROM workflow_process_definition_access a
                  WHERE a.process_definition_id = pd.id
                    AND a.access_type = :accessType
              )
            SQL;

        $params = [
            'orgId' => $organizationId,
            'accessType' => $type->value,
        ];
        $types = [];

        // If user has no dept and no roles, they can only access unrestricted definitions
        if (null === $departmentId && [] === $roleIds) {
            /** @var list<array{id: string}> $rows */
            $rows = $this->connection->executeQuery($unrestricted, $params, $types)->fetchAllAssociative();

            return array_map(static fn (array $row): string => $row['id'], $rows);
        }

        // Definitions that have access rows matching the user's dept or roles
        $conditions = [];

        if (null !== $departmentId) {
            $conditions[] = '(a.department_id = :deptId AND a.role_id IS NULL)';
            $params['deptId'] = $departmentId;

            if ([] !== $roleIds) {
                $conditions[] = '(a.department_id = :deptId AND a.role_id IN (:roleIds))';
                $conditions[] = '(a.department_id IS NULL AND a.role_id IN (:roleIds))';
                $params['roleIds'] = $roleIds;
                $types['roleIds'] = ArrayParameterType::STRING;
            }
        } else {
            $conditions[] = '(a.department_id IS NULL AND a.role_id IN (:roleIds))';
            $params['roleIds'] = $roleIds;
            $types['roleIds'] = ArrayParameterType::STRING;
        }

        $restricted = 'SELECT DISTINCT a.process_definition_id AS id'
            . ' FROM workflow_process_definition_access a'
            . ' WHERE a.organization_id = :orgId'
            . ' AND a.access_type = :accessType'
            . ' AND (' . implode(' OR ', $conditions) . ')';

        $sql = $unrestricted . ' UNION ' . $restricted;

        /** @var list<array{id: string}> $rows */
        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

        return array_map(static fn (array $row): string => $row['id'], $rows);
    }

    public function save(ProcessDefinitionAccess $access): void
    {
        $this->entityManager->persist($access);
        $this->entityManager->flush();
    }

    public function removeByProcessDefinitionId(string $processDefinitionId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(ProcessDefinitionAccess::class, 'a')
            ->where('a.processDefinitionId = :defId')
            ->setParameter('defId', $processDefinitionId)
            ->getQuery()
            ->execute();
    }

    public function removeByProcessDefinitionIdAndType(string $processDefinitionId, AccessType $type): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(ProcessDefinitionAccess::class, 'a')
            ->where('a.processDefinitionId = :defId')
            ->andWhere('a.accessType = :type')
            ->setParameter('defId', $processDefinitionId)
            ->setParameter('type', $type->value)
            ->getQuery()
            ->execute();
    }
}
