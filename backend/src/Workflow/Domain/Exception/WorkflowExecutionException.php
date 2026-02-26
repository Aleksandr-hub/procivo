<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class WorkflowExecutionException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function tokenNotFound(string $tokenId): self
    {
        $e = new self(\sprintf('Token with ID "%s" not found.', $tokenId));
        $e->statusCode = 404;
        $e->translationKey = 'error.workflow_token_not_found';
        $e->translationParams = ['%id%' => $tokenId];

        return $e;
    }

    public static function invalidTransition(string $reason): self
    {
        $e = new self(\sprintf('Workflow execution error: %s', $reason));
        $e->statusCode = 422;
        $e->translationKey = 'error.workflow_execution_error';
        $e->translationParams = ['%reason%' => $reason];

        return $e;
    }

    public static function processNotRunning(string $processInstanceId): self
    {
        $e = new self(\sprintf('Process instance "%s" is not in running state.', $processInstanceId));
        $e->statusCode = 422;
        $e->translationKey = 'error.process_not_running';
        $e->translationParams = ['%id%' => $processInstanceId];

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
