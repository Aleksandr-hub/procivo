<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionEffect;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;
use App\Organization\Domain\ValueObject\UserPermissionOverrideId;

class UserPermissionOverride
{
    private string $id;
    private string $employeeId;
    private string $organizationId;
    private string $resource;
    private string $action;
    private string $effect;
    private string $scope;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        UserPermissionOverrideId $id,
        EmployeeId $employeeId,
        OrganizationId $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
        PermissionEffect $effect,
        PermissionScope $scope,
    ): self {
        $override = new self();
        $override->id = $id->value();
        $override->employeeId = $employeeId->value();
        $override->organizationId = $organizationId->value();
        $override->resource = $resource->value;
        $override->action = $action->value;
        $override->effect = $effect->value;
        $override->scope = $scope->value;
        $override->createdAt = new \DateTimeImmutable();

        return $override;
    }

    public function id(): UserPermissionOverrideId
    {
        return UserPermissionOverrideId::fromString($this->id);
    }

    public function employeeId(): EmployeeId
    {
        return EmployeeId::fromString($this->employeeId);
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

    public function effect(): PermissionEffect
    {
        return PermissionEffect::from($this->effect);
    }

    public function scope(): PermissionScope
    {
        return PermissionScope::from($this->scope);
    }

    public function isAllow(): bool
    {
        return $this->effect() === PermissionEffect::Allow;
    }

    public function isDeny(): bool
    {
        return $this->effect() === PermissionEffect::Deny;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
