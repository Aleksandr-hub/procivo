<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface SoftDeletableInterface
{
    public function softDelete(): void;

    public function restore(): void;

    public function isDeleted(): bool;

    public function deletedAt(): ?\DateTimeImmutable;
}
