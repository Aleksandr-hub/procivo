<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Workflow\Domain\ValueObject\AccessType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionAccessId;

class ProcessDefinitionAccess
{
    private string $id;
    private string $processDefinitionId;
    private string $organizationId;
    private ?string $departmentId;
    private ?string $roleId;
    private string $accessType;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        ProcessDefinitionAccessId $id,
        string $processDefinitionId,
        string $organizationId,
        ?string $departmentId,
        ?string $roleId,
        AccessType $accessType,
    ): self {
        $access = new self();
        $access->id = $id->value();
        $access->processDefinitionId = $processDefinitionId;
        $access->organizationId = $organizationId;
        $access->departmentId = $departmentId;
        $access->roleId = $roleId;
        $access->accessType = $accessType->value;
        $access->createdAt = new \DateTimeImmutable();

        return $access;
    }

    public function id(): ProcessDefinitionAccessId
    {
        return ProcessDefinitionAccessId::fromString($this->id);
    }

    public function processDefinitionId(): string
    {
        return $this->processDefinitionId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function departmentId(): ?string
    {
        return $this->departmentId;
    }

    public function roleId(): ?string
    {
        return $this->roleId;
    }

    public function accessType(): AccessType
    {
        return AccessType::from($this->accessType);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
