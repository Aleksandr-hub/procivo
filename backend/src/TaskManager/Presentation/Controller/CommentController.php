<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\AddComment\AddCommentCommand;
use App\TaskManager\Application\Command\DeleteComment\DeleteCommentCommand;
use App\TaskManager\Application\Command\UpdateComment\UpdateCommentCommand;
use App\TaskManager\Application\Query\ListComments\ListCommentsQuery;
use App\TaskManager\Domain\ValueObject\CommentId;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Comments')]
#[Route('/api/v1/organizations/{organizationId}/tasks/{taskId}/comments', name: 'api_v1_comments_')]
final readonly class CommentController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[OA\Get(summary: 'List comments on a task')]
    #[OA\Response(response: 200, description: 'Comment list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\TaskManager\Application\DTO\CommentDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $comments = $this->queryBus->ask(new ListCommentsQuery($taskId));

        return new JsonResponse($comments);
    }

    #[OA\Post(
        summary: 'Add comment to task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body'],
                properties: [
                    new OA\Property(property: 'body', type: 'string'),
                    new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true, description: 'Parent comment ID for threading'),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Comment created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');
        $data = $this->decodeJson($request);

        $id = CommentId::generate()->value();
        $userId = $this->currentUserProvider->getUserId();

        $this->commandBus->dispatch(new AddCommentCommand(
            id: $id,
            taskId: $taskId,
            authorId: $userId,
            body: $data['body'] ?? '',
            parentId: isset($data['parent_id']) && \is_string($data['parent_id']) ? $data['parent_id'] : null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: 'Update a comment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body'],
                properties: [new OA\Property(property: 'body', type: 'string')],
            ),
        ),
    )]
    #[OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Comment updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{commentId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $taskId, string $commentId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateCommentCommand(
            commentId: $commentId,
            body: $data['body'] ?? '',
        ));

        return new JsonResponse(['message' => 'Comment updated.']);
    }

    #[OA\Delete(summary: 'Delete a comment')]
    #[OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Comment deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{commentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $taskId, string $commentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $this->commandBus->dispatch(new DeleteCommentCommand($commentId));

        return new JsonResponse(['message' => 'Comment deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
