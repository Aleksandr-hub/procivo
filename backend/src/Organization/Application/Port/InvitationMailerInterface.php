<?php

declare(strict_types=1);

namespace App\Organization\Application\Port;

interface InvitationMailerInterface
{
    public function sendInvitation(
        string $recipientEmail,
        string $organizationName,
        string $inviterName,
        string $token,
        \DateTimeImmutable $expiresAt,
    ): void;
}
