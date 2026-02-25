<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionId;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;
use App\Organization\Domain\ValueObject\RoleId;

class Permission
{
    private string $id;
    private string $roleId;
    private string $organizationId;
    private string $resource;
    private string $action;
    private string $scope;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        PermissionId $id,
        RoleId $roleId,
        OrganizationId $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
        PermissionScope $scope,
    ): self {
        $permission = new self();
        $permission->id = $id->value();
        $permission->roleId = $roleId->value();
        $permission->organizationId = $organizationId->value();
        $permission->resource = $resource->value;
        $permission->action = $action->value;
        $permission->scope = $scope->value;
        $permission->createdAt = new \DateTimeImmutable();

        return $permission;
    }

    public function id(): PermissionId
    {
        return PermissionId::fromString($this->id);
    }

    public function roleId(): RoleId
    {
        return RoleId::fromString($this->roleId);
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
