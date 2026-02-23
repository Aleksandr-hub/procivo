<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

final class JwtCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        $payload = $event->getData();
        $payload['user_id'] = $user->getId();

        $event->setData($payload);
    }
}
