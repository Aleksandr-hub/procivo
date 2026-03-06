<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Controller;

use App\Notification\Application\Command\MarkAllAsRead\MarkAllAsReadCommand;
use App\Notification\Application\Command\MarkAsRead\MarkAsReadCommand;
use App\Notification\Application\Command\SavePreferences\SavePreferencesCommand;
use App\Notification\Application\Query\CountUnread\CountUnreadQuery;
use App\Notification\Application\Query\GetPreferences\GetPreferencesQuery;
use App\Notification\Application\Query\ListNotifications\ListNotificationsQuery;
use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Notifications')]
#[Route('/api/v1/notifications', name: 'api_v1_notifications_')]
final readonly class NotificationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[OA\Get(
        summary: 'List notifications for current user',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['task_assigned', 'task_completed', 'process_completed', 'invitation_created', 'process_started'])),
        ],
    )]
    #[OA\Response(response: 200, description: 'Notification list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\Notification\Application\DTO\NotificationDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();
        $limit = (int) $request->query->get('limit', '50');
        $offset = (int) $request->query->get('offset', '0');
        $type = $request->query->get('type');

        $notifications = $this->queryBus->ask(new ListNotificationsQuery($userId, $limit, $offset, $type));

        return new JsonResponse($notifications);
    }

    #[OA\Get(summary: 'Get unread notification count')]
    #[OA\Response(response: 200, description: 'Unread count', content: new OA\JsonContent(properties: [new OA\Property(property: 'count', type: 'integer')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();
        $count = $this->queryBus->ask(new CountUnreadQuery($userId));

        return new JsonResponse(['count' => $count]);
    }

    #[OA\Post(summary: 'Mark notification as read')]
    #[OA\Parameter(name: 'notificationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Marked as read', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('/{notificationId}/read', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(string $notificationId): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        $this->commandBus->dispatch(new MarkAsReadCommand($notificationId, $userId));

        return new JsonResponse(['message' => 'Notification marked as read.']);
    }

    #[OA\Post(summary: 'Mark all notifications as read')]
    #[OA\Response(response: 200, description: 'All marked as read', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('/read-all', name: 'mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        $this->commandBus->dispatch(new MarkAllAsReadCommand($userId));

        return new JsonResponse(['message' => 'All notifications marked as read.']);
    }

    #[OA\Get(summary: 'Get notification preferences')]
    #[OA\Response(
        response: 200,
        description: 'Notification preferences by type',
        content: new OA\JsonContent(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                properties: [
                    new OA\Property(property: 'in_app', type: 'boolean'),
                    new OA\Property(property: 'email', type: 'boolean'),
                ],
                type: 'object',
            ),
        ),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('/preferences', name: 'get_preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        /** @var array<string, array{in_app: bool, email: bool}> $preferences */
        $preferences = $this->queryBus->ask(new GetPreferencesQuery($userId));

        return new JsonResponse($preferences);
    }

    #[OA\Put(
        summary: 'Update notification preferences',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                description: 'Map of notification type to channel preferences',
                additionalProperties: new OA\AdditionalProperties(
                    properties: [
                        new OA\Property(property: 'in_app', type: 'boolean'),
                        new OA\Property(property: 'email', type: 'boolean'),
                    ],
                    type: 'object',
                ),
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Preferences saved', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('/preferences', name: 'save_preferences', methods: ['PUT'])]
    public function savePreferences(Request $request): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        /** @var array<string, array{in_app: bool, email: bool}> $preferences */
        $preferences = (array) json_decode((string) $request->getContent(), true);

        $this->commandBus->dispatch(new SavePreferencesCommand($userId, $preferences));

        return new JsonResponse(['message' => 'Preferences saved.']);
    }
}
