<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\SavePreferences;

use App\Notification\Domain\Entity\NotificationPreference;
use App\Notification\Domain\Repository\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationPreferenceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SavePreferencesHandler
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
    ) {
    }

    public function __invoke(SavePreferencesCommand $command): void
    {
        foreach ($command->preferences as $eventType => $channels) {
            foreach ($channels as $channel => $enabled) {
                $preference = $this->preferenceRepository->findByUserIdAndEventTypeAndChannel(
                    $command->userId,
                    $eventType,
                    $channel,
                );

                if (null === $preference) {
                    $preference = NotificationPreference::create(
                        NotificationPreferenceId::generate(),
                        $command->userId,
                        $eventType,
                        $channel,
                        $enabled,
                    );
                } else {
                    $preference->updateEnabled($enabled);
                }

                $this->preferenceRepository->save($preference);
            }
        }
    }
}
