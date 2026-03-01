<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Workflow\Domain\Event\ProcessStartedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessStarted
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ProcessStartedEvent $event): void
    {
        $user = $this->userRepository->findById(UserId::fromString($event->startedBy));
        $recipientEmail = null !== $user ? $user->email()->value() : null;

        $this->notificationDispatcher->dispatch([
            'recipientId' => $event->startedBy,
            'recipientEmail' => $recipientEmail,
            'type' => NotificationType::ProcessStarted,
            'title' => 'Process started',
            'body' => 'Your process has been started successfully.',
            'relatedEntityId' => $event->processInstanceId,
            'relatedEntityType' => 'process_instance',
            'emailSubject' => 'Process started',
            'emailTemplate' => 'email/notification/process_started.html.twig',
            'emailContext' => ['processInstanceId' => $event->processInstanceId],
        ]);
    }
}
