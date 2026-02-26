<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\TaskAttachment;

final readonly class TaskAttachmentDTO
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public string $uploadedBy,
        public string $uploadedAt,
        public ?string $downloadUrl = null,
    ) {
    }

    public static function fromEntity(TaskAttachment $attachment, ?string $downloadUrl = null): self
    {
        return new self(
            id: $attachment->id()->value(),
            taskId: $attachment->taskId(),
            originalName: $attachment->originalName(),
            mimeType: $attachment->mimeType(),
            fileSize: $attachment->fileSize(),
            uploadedBy: $attachment->uploadedBy(),
            uploadedAt: $attachment->uploadedAt()->format(\DateTimeInterface::ATOM),
            downloadUrl: $downloadUrl,
        );
    }
}
