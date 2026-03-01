<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\Logout\LogoutCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\UpdateProfile\UpdateProfileCommand;
use App\Identity\Application\Command\UploadAvatar\UploadAvatarCommand;
use App\Identity\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\Identity\Application\Query\Login\LoginQuery;
use App\Identity\Application\Query\RefreshToken\RefreshTokenQuery;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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

        $this->commandBus->dispatch(new RegisterUserCommand(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
        ));

        return new JsonResponse(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        #[Autowire(service: 'limiter.login')] RateLimiterFactory $loginLimiter,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        $limiter = $loginLimiter->create(($data['email'] ?? '').'|'.($request->getClientIp() ?? ''));
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many login attempts.');
        }

        $dto = $this->queryBus->ask(new LoginQuery(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        ));

        return new JsonResponse($dto);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        $dto = $this->queryBus->ask(new RefreshTokenQuery(
            refreshToken: $data['refresh_token'] ?? '',
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        ));

        return new JsonResponse($dto);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new LogoutCommand(
            refreshToken: $data['refresh_token'] ?? '',
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $dto = $this->queryBus->ask(new GetCurrentUserQuery($user->getId()));

        return new JsonResponse($dto);
    }

    #[Route('/me', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateProfileCommand(
            userId: $user->getId(),
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            email: $data['email'] ?? '',
        ));

        return new JsonResponse(['message' => 'Profile updated.']);
    }

    #[Route('/me/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        $file = $request->files->get('avatar');

        if (null === $file) {
            throw new BadRequestHttpException('No file uploaded.');
        }

        $content = file_get_contents($file->getPathname());

        if (false === $content) {
            throw new \RuntimeException('Failed to read uploaded file.');
        }

        $this->commandBus->dispatch(new UploadAvatarCommand(
            userId: $user->getId(),
            fileContent: $content,
            originalName: $file->getClientOriginalName(),
        ));

        return new JsonResponse(['message' => 'Avatar uploaded.']);
    }

    #[Route('/password', name: 'password', methods: ['PUT'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] SecurityUser $user,
    ): JsonResponse {
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new ChangePasswordCommand(
            userId: $user->getId(),
            currentPassword: $data['current_password'] ?? '',
            newPassword: $data['new_password'] ?? '',
        ));

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
