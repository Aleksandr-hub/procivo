<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $message = $exception instanceof TranslatableExceptionInterface
            ? $this->translator->trans($exception->getTranslationKey(), $exception->getTranslationParams())
            : $exception->getMessage();

        $event->setResponse(new JsonResponse(
            ['error' => $message],
            $exception->getStatusCode(),
        ));
    }
}
