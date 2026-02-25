<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Service;

use App\Organization\Application\Port\InvitationMailerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class SymfonyInvitationMailer implements InvitationMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $frontendUrl,
        private string $fromEmail,
    ) {
    }

    public function sendInvitation(
        string $recipientEmail,
        string $organizationName,
        string $inviterName,
        string $token,
        \DateTimeImmutable $expiresAt,
    ): void {
        $acceptUrl = \sprintf('%s/invitation/accept?token=%s', rtrim($this->frontendUrl, '/'), $token);

        $html = $this->twig->render('email/invitation.html.twig', [
            'organizationName' => $organizationName,
            'inviterName' => $inviterName,
            'acceptUrl' => $acceptUrl,
            'expiresAt' => $expiresAt,
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipientEmail)
            ->subject(\sprintf('You\'ve been invited to %s on Procivo', $organizationName))
            ->html($html);

        $this->mailer->send($email);
    }
}
