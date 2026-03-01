<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Organization\Domain\Event\InvitationCreatedEvent;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnInvitationCreated
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(InvitationCreatedEvent $event): void
    {
        // Check if the invited email belongs to an existing user
        $invitedUser = $this->userRepository->findByEmail(new Email($event->email));

        if (null === $invitedUser) {
            // No in-app notification possible for non-existing users
            // Invitation email is already sent by InviteUserHandler via InvitationMailerInterface
            return;
        }

        // Send in-app notification only — invitation email was already sent separately
        $this->notificationDispatcher->dispatch([
            'recipientId' => $invitedUser->id()->value(),
            'recipientEmail' => null,
            'type' => NotificationType::InvitationReceived,
            'title' => 'Organization invitation',
            'body' => 'You have received an invitation to join an organization.',
            'relatedEntityId' => $event->organizationId,
            'relatedEntityType' => 'organization',
            'emailTemplate' => null,
        ]);
    }
}
