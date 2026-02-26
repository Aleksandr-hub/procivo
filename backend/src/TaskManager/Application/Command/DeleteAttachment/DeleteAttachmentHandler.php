<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteAttachment;

use App\TaskManager\Application\Port\FileStorageInterface;
use App\TaskManager\Domain\Exception\AttachmentNotFoundException;
use App\TaskManager\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AttachmentId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteAttachmentHandler
{
    public function __construct(
        private TaskAttachmentRepositoryInterface $attachmentRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function __invoke(DeleteAttachmentCommand $command): void
    {
        $attachment = $this->attachmentRepository->findById(AttachmentId::fromString($command->attachmentId));

        if (null === $attachment) {
            throw AttachmentNotFoundException::withId($command->attachmentId);
        }

        $this->fileStorage->delete($attachment->storagePath());
        $this->attachmentRepository->remove($attachment);
    }
}
