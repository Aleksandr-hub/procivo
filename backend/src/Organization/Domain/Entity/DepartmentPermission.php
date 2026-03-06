<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPermissionId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;

class DepartmentPermission
{
    private string $id;
    private string $departmentId;
    private string $organizationId;
    private string $resource;
    private string $action;
    private string $scope;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        DepartmentPermissionId $id,
        DepartmentId $departmentId,
        OrganizationId $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
        PermissionScope $scope,
    ): self {
        $permission = new self();
        $permission->id = $id->value();
        $permission->departmentId = $departmentId->value();
        $permission->organizationId = $organizationId->value();
        $permission->resource = $resource->value;
        $permission->action = $action->value;
        $permission->scope = $scope->value;
        $permission->createdAt = new \DateTimeImmutable();

        return $permission;
    }

    public function id(): DepartmentPermissionId
    {
        return DepartmentPermissionId::fromString($this->id);
    }

    public function departmentId(): DepartmentId
    {
        return DepartmentId::fromString($this->departmentId);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function resource(): PermissionResource
    {
        return PermissionResource::from($this->resource);
    }

    public function action(): PermissionAction
    {
        return PermissionAction::from($this->action);
    }

    public function scope(): PermissionScope
    {
        return PermissionScope::from($this->scope);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
