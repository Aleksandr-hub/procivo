<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\TaskAttachment;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'File attachment on a task')]
final readonly class TaskAttachmentDTO
{
    public function __construct(
        #[OA\Property(description: 'Attachment UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Task UUID', format: 'uuid')]
        public string $taskId,
        #[OA\Property(description: 'Original file name')]
        public string $originalName,
        #[OA\Property(description: 'MIME type', example: 'application/pdf')]
        public string $mimeType,
        #[OA\Property(description: 'File size in bytes')]
        public int $fileSize,
        #[OA\Property(description: 'Uploader user UUID', format: 'uuid')]
        public string $uploadedBy,
        #[OA\Property(description: 'Upload timestamp', format: 'date-time')]
        public string $uploadedAt,
        #[OA\Property(description: 'Pre-signed download URL', format: 'uri', nullable: true)]
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
