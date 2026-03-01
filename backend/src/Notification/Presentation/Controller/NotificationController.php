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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/notifications', name: 'api_v1_notifications_')]
final readonly class NotificationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

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

    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();
        $count = $this->queryBus->ask(new CountUnreadQuery($userId));

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/{notificationId}/read', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(string $notificationId): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        $this->commandBus->dispatch(new MarkAsReadCommand($notificationId, $userId));

        return new JsonResponse(['message' => 'Notification marked as read.']);
    }

    #[Route('/read-all', name: 'mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        $this->commandBus->dispatch(new MarkAllAsReadCommand($userId));

        return new JsonResponse(['message' => 'All notifications marked as read.']);
    }

    #[Route('/preferences', name: 'get_preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $userId = $this->currentUserProvider->getUserId();

        /** @var array<string, array{in_app: bool, email: bool}> $preferences */
        $preferences = $this->queryBus->ask(new GetPreferencesQuery($userId));

        return new JsonResponse($preferences);
    }

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
