<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessDefinitionAccess;

use App\Workflow\Application\DTO\ProcessDefinitionAccessDTO;
use App\Workflow\Domain\Repository\ProcessDefinitionAccessRepositoryInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessDefinitionAccessHandler
{
    public function __construct(
        private ProcessDefinitionAccessRepositoryInterface $accessRepository,
        private Connection $connection,
    ) {
    }

    /**
     * @return list<ProcessDefinitionAccessDTO>
     */
    public function __invoke(GetProcessDefinitionAccessQuery $query): array
    {
        $entries = $this->accessRepository->findByProcessDefinitionId($query->processDefinitionId);

        if ([] === $entries) {
            return [];
        }

        // Collect department and role IDs for batch name resolution
        $departmentIds = [];
        $roleIds = [];

        foreach ($entries as $entry) {
            if (null !== $entry->departmentId()) {
                $departmentIds[$entry->departmentId()] = true;
            }
            if (null !== $entry->roleId()) {
                $roleIds[$entry->roleId()] = true;
            }
        }

        $departmentNames = $this->resolveDepartmentNames(array_keys($departmentIds));
        $roleNames = $this->resolveRoleNames(array_keys($roleIds));

        return array_map(
            static fn ($entry) => ProcessDefinitionAccessDTO::fromEntity(
                $entry,
                $departmentNames[$entry->departmentId()] ?? null,
                $roleNames[$entry->roleId()] ?? null,
            ),
            $entries,
        );
    }

    /**
     * @param list<string> $ids
     * @return array<string, string>
     */
    private function resolveDepartmentNames(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<array{id: string, name: string}> $rows */
        $rows = $this->connection->executeQuery(
            'SELECT id, name FROM organization_departments WHERE id IN (?)',
            [$ids],
            [ArrayParameterType::STRING],
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = $row['name'];
        }

        return $map;
    }

    /**
     * @param list<string> $ids
     * @return array<string, string>
     */
    private function resolveRoleNames(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<array{id: string, name: string}> $rows */
        $rows = $this->connection->executeQuery(
            'SELECT id, name FROM organization_roles WHERE id IN (?)',
            [$ids],
            [ArrayParameterType::STRING],
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = $row['name'];
        }

        return $map;
    }
}
