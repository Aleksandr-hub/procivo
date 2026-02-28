<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class TaskClaimException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function notAPoolTask(string $taskId): self
    {
        $e = new self(\sprintf('Task "%s" is not a pool task and cannot be claimed.', $taskId));
        $e->statusCode = 422;
        $e->translationKey = 'error.task_claim_not_pool';
        $e->translationParams = ['%id%' => $taskId];

        return $e;
    }

    public static function alreadyClaimed(string $taskId): self
    {
        $e = new self(\sprintf('Task "%s" is already claimed.', $taskId));
        $e->statusCode = 409;
        $e->translationKey = 'error.task_claim_already_claimed';
        $e->translationParams = ['%id%' => $taskId];

        return $e;
    }

    public static function notClaimed(string $taskId): self
    {
        $e = new self(\sprintf('Task "%s" is not currently claimed.', $taskId));
        $e->statusCode = 422;
        $e->translationKey = 'error.task_claim_not_claimed';
        $e->translationParams = ['%id%' => $taskId];

        return $e;
    }

    public static function notEligible(string $employeeId, string $taskId): self
    {
        $e = new self(\sprintf('Employee "%s" is not eligible to claim task "%s".', $employeeId, $taskId));
        $e->statusCode = 403;
        $e->translationKey = 'error.task_claim_not_eligible';
        $e->translationParams = ['%employeeId%' => $employeeId, '%taskId%' => $taskId];

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
