<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\RefreshToken;

use App\Identity\Application\DTO\AuthTokensDTO;
use App\Identity\Application\Port\JwtTokenManagerInterface;
use App\Identity\Application\Port\RefreshTokenManagerInterface;
use App\Identity\Domain\Exception\InvalidCredentialsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class RefreshTokenHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private JwtTokenManagerInterface $jwtTokenManager,
        private RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    public function __invoke(RefreshTokenQuery $query): AuthTokensDTO
    {
        $payload = $this->refreshTokenManager->validate($query->refreshToken);

        if (null === $payload) {
            throw InvalidCredentialsException::invalidRefreshToken();
        }

        $user = $this->userRepository->findById(UserId::fromString($payload['user_id']));

        if (null === $user || !$user->isActive()) {
            $this->refreshTokenManager->revoke($query->refreshToken);

            throw InvalidCredentialsException::invalidRefreshToken();
        }

        $newRefreshToken = $this->refreshTokenManager->rotate(
            oldToken: $query->refreshToken,
            ip: $query->ip,
            userAgent: $query->userAgent,
        );

        if (null === $newRefreshToken) {
            throw InvalidCredentialsException::invalidRefreshToken();
        }

        $accessToken = $this->jwtTokenManager->create(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            roles: $user->roles(),
        );

        return new AuthTokensDTO(
            accessToken: $accessToken,
            refreshToken: $newRefreshToken,
        );
    }
}
