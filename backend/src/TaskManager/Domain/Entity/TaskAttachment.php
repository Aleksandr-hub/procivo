<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\TaskManager\Domain\ValueObject\AttachmentId;

class TaskAttachment
{
    private string $id;
    private string $taskId;
    private string $originalName;
    private string $storagePath;
    private string $mimeType;
    private int $fileSize;
    private string $uploadedBy;
    private \DateTimeImmutable $uploadedAt;

    private function __construct()
    {
    }

    public static function create(
        AttachmentId $id,
        string $taskId,
        string $originalName,
        string $storagePath,
        string $mimeType,
        int $fileSize,
        string $uploadedBy,
    ): self {
        $attachment = new self();
        $attachment->id = $id->value();
        $attachment->taskId = $taskId;
        $attachment->originalName = $originalName;
        $attachment->storagePath = $storagePath;
        $attachment->mimeType = $mimeType;
        $attachment->fileSize = $fileSize;
        $attachment->uploadedBy = $uploadedBy;
        $attachment->uploadedAt = new \DateTimeImmutable();

        return $attachment;
    }

    public function id(): AttachmentId
    {
        return AttachmentId::fromString($this->id);
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function fileSize(): int
    {
        return $this->fileSize;
    }

    public function uploadedBy(): string
    {
        return $this->uploadedBy;
    }

    public function uploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
