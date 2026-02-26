<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class AttachmentNotFoundException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withId(string $id): self
    {
        $e = new self(\sprintf('Attachment with ID "%s" not found.', $id));
        $e->statusCode = 404;
        $e->translationKey = 'error.attachment_not_found';
        $e->translationParams = ['%id%' => $id];

        return $e;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /** @return array<string, string> */
    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }
}
