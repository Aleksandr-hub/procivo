<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UploadAttachment;

use App\Shared\Application\Command\CommandInterface;

final readonly class UploadAttachmentCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public string $fileContent,
        public string $uploadedBy,
    ) {
    }
}
