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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $comments = $this->queryBus->ask(new ListCommentsQuery($taskId));

        return new JsonResponse($comments);
    }

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
