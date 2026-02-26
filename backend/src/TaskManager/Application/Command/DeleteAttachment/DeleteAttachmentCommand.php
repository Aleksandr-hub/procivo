<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteAttachment;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteAttachmentCommand implements CommandInterface
{
    public function __construct(
        public string $attachmentId,
    ) {
    }
}
