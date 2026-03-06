<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Label;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Task label for categorization')]
final readonly class LabelDTO
{
    public function __construct(
        #[OA\Property(description: 'Label UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Label name')]
        public string $name,
        #[OA\Property(description: 'Label color hex code', example: '#EF4444')]
        public string $color,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
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
