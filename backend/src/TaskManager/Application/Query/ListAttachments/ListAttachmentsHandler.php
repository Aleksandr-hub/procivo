<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListAttachments;

use App\TaskManager\Application\DTO\TaskAttachmentDTO;
use App\TaskManager\Application\Port\FileStorageInterface;
use App\TaskManager\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAttachmentsHandler
{
    public function __construct(
        private TaskAttachmentRepositoryInterface $attachmentRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    /**
     * @return list<TaskAttachmentDTO>
     */
    public function __invoke(ListAttachmentsQuery $query): array
    {
        $attachments = $this->attachmentRepository->findByTaskId(TaskId::fromString($query->taskId));

        return array_map(
            fn ($a) => TaskAttachmentDTO::fromEntity($a, $this->fileStorage->getUrl($a->storagePath())),
            $attachments,
        );
    }
}
