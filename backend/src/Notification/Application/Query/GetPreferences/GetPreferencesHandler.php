<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\GetPreferences;

use App\Notification\Domain\Repository\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetPreferencesHandler
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
    ) {
    }

    /**
     * Returns a map of eventType => {in_app: bool, email: bool} for all 7 event types.
     * Missing rows use defaults: in_app=true, email=false.
     *
     * @return array<string, array{in_app: bool, email: bool}>
     */
    public function __invoke(GetPreferencesQuery $query): array
    {
        $preferences = $this->preferenceRepository->findByUserId($query->userId);

        // Build a lookup: eventType+channel => enabled
        $lookup = [];
        foreach ($preferences as $preference) {
            $lookup[$preference->eventType()][$preference->channel()] = $preference->enabled();
        }

        // Build result map covering all event types with defaults
        $result = [];
        foreach (NotificationType::cases() as $type) {
            $result[$type->value] = [
                'in_app' => $lookup[$type->value]['in_app'] ?? true,
                'email' => $lookup[$type->value]['email'] ?? false,
            ];
        }

        return $result;
    }
}
