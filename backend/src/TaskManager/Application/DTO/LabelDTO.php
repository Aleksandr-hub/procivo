<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Label;

final readonly class LabelDTO
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $color,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Label $label): self
    {
        return new self(
            id: $label->id()->value(),
            organizationId: $label->organizationId(),
            name: $label->name(),
            color: $label->color(),
            createdAt: $label->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
