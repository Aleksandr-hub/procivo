<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UploadAttachment;

use App\TaskManager\Application\Port\FileStorageInterface;
use App\TaskManager\Domain\Entity\TaskAttachment;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AttachmentId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UploadAttachmentHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskAttachmentRepositoryInterface $attachmentRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function __invoke(UploadAttachmentCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $storagePath = \sprintf('tasks/%s/%s/%s', $command->taskId, $command->id, $command->originalName);

        $this->fileStorage->upload($storagePath, $command->fileContent, $command->mimeType);

        $attachment = TaskAttachment::create(
            id: AttachmentId::fromString($command->id),
            taskId: $command->taskId,
            originalName: $command->originalName,
            storagePath: $storagePath,
            mimeType: $command->mimeType,
            fileSize: $command->fileSize,
            uploadedBy: $command->uploadedBy,
        );

        $this->attachmentRepository->save($attachment);
    }
}
