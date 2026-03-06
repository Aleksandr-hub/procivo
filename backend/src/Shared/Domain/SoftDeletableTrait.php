<?php

declare(strict_types=1);

namespace App\Shared\Domain;

trait SoftDeletableTrait
{
    private ?\DateTimeImmutable $deletedAt = null;

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
