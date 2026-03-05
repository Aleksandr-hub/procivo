<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListEmployeesHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private DepartmentRepositoryInterface $departmentRepository,
        private PositionRepositoryInterface $positionRepository,
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    /**
     * @return list<EmployeeDTO>
     */
    public function __invoke(ListEmployeesQuery $query): array
    {
        $orgId = OrganizationId::fromString($query->organizationId);

        $employees = null !== $query->departmentId
            ? $this->employeeRepository->findByDepartmentId(DepartmentId::fromString($query->departmentId))
            : $this->employeeRepository->findByOrganizationId($orgId);

        $deptMap = [];
        foreach ($this->departmentRepository->findByOrganizationId($orgId) as $dept) {
            $deptMap[$dept->id()->value()] = $dept->name();
        }

        $posMap = [];
        foreach ($this->positionRepository->findByOrganizationId($orgId) as $pos) {
            $posMap[$pos->id()->value()] = $pos->name()->value();
        }

        $userIds = array_unique(array_map(static fn ($emp) => $emp->userId(), $employees));
        $userMap = [];
        foreach ($userIds as $userId) {
            $user = $this->userRepository->findById(UserId::fromString($userId));
            if (null !== $user) {
                $avatarUrl = null;
                if (null !== $user->avatarPath()) {
                    $avatarUrl = $this->avatarStorage->getUrl($user->avatarPath());
                }
                $userMap[$userId] = [
                    'fullName' => $user->firstName().' '.$user->lastName(),
                    'email' => $user->email()->value(),
                    'avatarUrl' => $avatarUrl,
                ];
            }
        }

        return array_map(
            static fn ($emp) => EmployeeDTO::fromEntity(
                $emp,
                departmentName: $deptMap[$emp->departmentId()->value()] ?? null,
                positionName: $posMap[$emp->positionId()->value()] ?? null,
                userFullName: $userMap[$emp->userId()]['fullName'] ?? null,
                userEmail: $userMap[$emp->userId()]['email'] ?? null,
                userAvatarUrl: $userMap[$emp->userId()]['avatarUrl'] ?? null,
            ),
            $employees,
        );
    }
}
