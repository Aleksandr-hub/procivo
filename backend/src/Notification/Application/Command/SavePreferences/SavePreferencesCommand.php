<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\SavePreferences;

use App\Shared\Application\Command\CommandInterface;

final readonly class SavePreferencesCommand implements CommandInterface
{
    /**
     * @param array<string, array{in_app: bool, email: bool}> $preferences
     */
    public function __construct(
        public string $userId,
        public array $preferences,
    ) {
    }
}
