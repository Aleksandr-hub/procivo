<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Hashing;

use App\Identity\Application\Port\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasherFactory->getPasswordHasher('default')->hash($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $this->hasherFactory->getPasswordHasher('default')->verify($hashedPassword, $plainPassword);
    }
}
