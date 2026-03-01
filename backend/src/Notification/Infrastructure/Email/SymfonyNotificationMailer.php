<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Email;

use App\Notification\Application\Port\NotificationMailerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class SymfonyNotificationMailer implements NotificationMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function send(string $recipientEmail, string $subject, string $template, array $context): void
    {
        $html = $this->twig->render($template, $context);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipientEmail)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
