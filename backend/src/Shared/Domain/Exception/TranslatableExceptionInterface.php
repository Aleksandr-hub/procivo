<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

interface TranslatableExceptionInterface
{
    public function getTranslationKey(): string;

    /**
     * @return array<string, string>
     */
    public function getTranslationParams(): array;
}
