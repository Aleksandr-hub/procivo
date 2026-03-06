<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Login;

use App\Identity\Application\DTO\AuthTokensDTO;
use App\Identity\Application\DTO\TwoFactorChallengeDTO;
use App\Identity\Application\Port\JwtTokenManagerInterface;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Application\Port\RefreshTokenManagerInterface;
use App\Identity\Domain\Exception\InvalidCredentialsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Infrastructure\Security\RememberDeviceService;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private JwtTokenManagerInterface $jwtTokenManager,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private RememberDeviceService $rememberDeviceService,
    ) {
    }

    public function __invoke(LoginQuery $query): AuthTokensDTO|TwoFactorChallengeDTO
    {
        try {
            $email = new Email($query->email);
        } catch (\App\Shared\Domain\Exception\InvalidArgumentException) {
            throw InvalidCredentialsException::invalidEmailOrPassword();
        }

        $user = $this->userRepository->findByEmail($email);

        if (null === $user) {
            throw InvalidCredentialsException::invalidEmailOrPassword();
        }

        if (!$this->passwordHasher->verify($user->password()->value(), $query->password)) {
            throw InvalidCredentialsException::invalidEmailOrPassword();
        }

        if (!$user->isActive()) {
            throw \App\Identity\Domain\Exception\UserNotActiveException::withId($user->id()->value());
        }

        if ($user->isTotpEnabled()) {
            // Check remember device cookie — skip 2FA if device is remembered
            if (null !== $query->rememberDeviceToken
                && null !== $query->userAgent
                && $this->rememberDeviceService->isDeviceRemembered(
                    $query->rememberDeviceToken,
                    $user->id()->value(),
                    $query->userAgent,
                )) {
                // Device remembered — fall through to issue full tokens
            } else {
                $partialToken = $this->jwtTokenManager->createPartial(
                    userId: $user->id()->value(),
                    email: $user->email()->value(),
                );

                return new TwoFactorChallengeDTO(partialToken: $partialToken);
            }
        }

        $accessToken = $this->jwtTokenManager->create(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            roles: $user->roles(),
        );

        $refreshToken = $this->refreshTokenManager->generate(
            userId: $user->id()->value(),
            ip: $query->ip,
            userAgent: $query->userAgent,
        );

        return new AuthTokensDTO(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
        );
    }
}
