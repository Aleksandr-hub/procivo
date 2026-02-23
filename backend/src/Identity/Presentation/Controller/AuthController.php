<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\Identity\Domain\Exception\InvalidCredentialsException;
use App\Identity\Domain\Exception\UserAlreadyExistsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Infrastructure\Security\RefreshTokenService;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Domain\ValueObject\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
final readonly class AuthController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        #[Autowire(service: 'limiter.register')] RateLimiterFactory $registerLimiter,
    ): JsonResponse {
        $limiter = $registerLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many registration attempts.');
        }

        $data = $this->decodeJson($request);

        try {
            $this->commandBus->dispatch(new RegisterUserCommand(
                email: $data['email'] ?? '',
                password: $data['password'] ?? '',
                firstName: $data['first_name'] ?? '',
                lastName: $data['last_name'] ?? '',
            ));
        } catch (UserAlreadyExistsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (\App\Shared\Domain\Exception\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        #[Autowire(service: 'limiter.login')] RateLimiterFactory $loginLimiter,
        UserRepositoryInterface $userRepository,
        \App\Identity\Application\Port\PasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenService $refreshTokenService,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $emailStr = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $limiter = $loginLimiter->create($emailStr.'|'.($request->getClientIp() ?? ''));
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many login attempts.');
        }

        try {
            $email = new Email($emailStr);
        } catch (\App\Shared\Domain\Exception\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findByEmail($email);

        if (null === $user) {
            return new JsonResponse(['error' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->verify($user->password()->value(), $password)) {
            return new JsonResponse(['error' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return new JsonResponse(
                ['error' => 'Account is not active.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $securityUser = new SecurityUser(
            id: $user->id()->value(),
            email: $user->email()->value(),
            password: $user->password()->value(),
            roles: $user->roles(),
        );

        $accessToken = $jwtManager->create($securityUser);
        $refreshToken = $refreshTokenService->generate(
            userId: $user->id()->value(),
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        );

        return new JsonResponse([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        RefreshTokenService $refreshTokenService,
        UserRepositoryInterface $userRepository,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $oldToken = $data['refresh_token'] ?? '';

        $payload = $refreshTokenService->validate($oldToken);

        if (null === $payload) {
            return new JsonResponse(['error' => 'Invalid or expired refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findById(
            \App\Identity\Domain\ValueObject\UserId::fromString($payload['user_id']),
        );

        if (null === $user || !$user->isActive()) {
            $refreshTokenService->revoke($oldToken);

            return new JsonResponse(['error' => 'User not found or inactive.'], Response::HTTP_UNAUTHORIZED);
        }

        $newRefreshToken = $refreshTokenService->rotate(
            oldToken: $oldToken,
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        );

        $securityUser = new SecurityUser(
            id: $user->id()->value(),
            email: $user->email()->value(),
            password: $user->password()->value(),
            roles: $user->roles(),
        );

        return new JsonResponse([
            'access_token' => $jwtManager->create($securityUser),
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(
        Request $request,
        RefreshTokenService $refreshTokenService,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $token = $data['refresh_token'] ?? '';

        if ('' !== $token) {
            $refreshTokenService->revoke($token);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $dto = $this->queryBus->ask(new GetCurrentUserQuery($user->getId()));

        return new JsonResponse($dto);
    }

    #[Route('/password', name: 'password', methods: ['PUT'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] SecurityUser $user,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        try {
            $this->commandBus->dispatch(new ChangePasswordCommand(
                userId: $user->getId(),
                currentPassword: $data['current_password'] ?? '',
                newPassword: $data['new_password'] ?? '',
            ));
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Password changed successfully.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        /* @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
