<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\TaskManager\Domain\ValueObject\LabelId;

class Label
{
    private string $id;
    private string $organizationId;
    private string $name;
    private string $color;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        LabelId $id,
        string $organizationId,
        string $name,
        string $color,
    ): self {
        $label = new self();
        $label->id = $id->value();
        $label->organizationId = $organizationId;
        $label->name = $name;
        $label->color = $color;
        $label->createdAt = new \DateTimeImmutable();

        return $label;
    }

    public function update(string $name, string $color): void
    {
        $this->name = $name;
        $this->color = $color;
    }

    public function id(): LabelId
    {
        return LabelId::fromString($this->id);
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
