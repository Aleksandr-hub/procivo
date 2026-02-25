<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
final class LocaleListener
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $acceptLanguage = $request->headers->get('Accept-Language');

        if (null === $acceptLanguage) {
            return;
        }

        $locale = \substr($acceptLanguage, 0, 2);

        if (\in_array($locale, ['en', 'uk'], true)) {
            $request->setLocale($locale);
        }
    }
}
