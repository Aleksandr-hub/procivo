<?php

declare(strict_types=1);

namespace App\Notification\Application\Port;

interface NotificationMailerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function send(string $recipientEmail, string $subject, string $template, array $context): void;
}
