<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\DeleteAttachment\DeleteAttachmentCommand;
use App\TaskManager\Application\Command\UploadAttachment\UploadAttachmentCommand;
use App\TaskManager\Application\Query\ListAttachments\ListAttachmentsQuery;
use App\TaskManager\Domain\ValueObject\AttachmentId;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Attachments')]
#[Route('/api/v1/organizations/{organizationId}/tasks/{taskId}/attachments', name: 'api_v1_attachments_')]
final readonly class AttachmentController
{
    private const int MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB

    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[OA\Get(summary: 'List attachments on a task')]
    #[OA\Response(response: 200, description: 'Attachment list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\TaskManager\Application\DTO\TaskAttachmentDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $attachments = $this->queryBus->ask(new ListAttachmentsQuery($taskId));

        return new JsonResponse($attachments);
    }

    #[OA\Post(
        summary: 'Upload file attachment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File to upload (max 20MB)'),
                    ],
                ),
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'File uploaded', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 400, description: 'No file or file too large')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'upload', methods: ['POST'])]
    public function upload(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $file = $request->files->get('file');

        if (null === $file) {
            return new JsonResponse(['error' => 'No file uploaded.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return new JsonResponse(['error' => 'File too large. Max 20MB.'], Response::HTTP_BAD_REQUEST);
        }

        $id = AttachmentId::generate()->value();
        $content = file_get_contents($file->getPathname());

        if (false === $content) {
            return new JsonResponse(['error' => 'Failed to read uploaded file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->commandBus->dispatch(new UploadAttachmentCommand(
            id: $id,
            taskId: $taskId,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
            fileSize: (int) $file->getSize(),
            fileContent: $content,
            uploadedBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Delete(summary: 'Delete an attachment')]
    #[OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Attachment deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{attachmentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $taskId, string $attachmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $this->commandBus->dispatch(new DeleteAttachmentCommand($attachmentId));

        return new JsonResponse(['message' => 'Attachment deleted.']);
    }
}
