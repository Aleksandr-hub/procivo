<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\ConfirmTwoFactor\ConfirmTwoFactorCommand;
use App\Identity\Application\Command\DisableTwoFactor\DisableTwoFactorCommand;
use App\Identity\Application\Command\EnableTwoFactor\EnableTwoFactorCommand;
use App\Identity\Application\DTO\TwoFactorSetupDTO;
use App\Identity\Application\Port\JwtTokenManagerInterface;
use App\Identity\Application\Port\RefreshTokenManagerInterface;
use App\Identity\Application\Port\TotpServiceInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Identity\Infrastructure\Security\RememberDeviceService;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Bus\CommandBusInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/v1/auth/2fa', name: 'api_v1_auth_2fa_')]
final readonly class TwoFactorController
{
    private const int MAX_VERIFY_ATTEMPTS = 5;
    private const int VERIFY_ATTEMPTS_TTL = 300;
    private const int REMEMBER_DEVICE_DAYS = 30;

    public function __construct(
        private CommandBusInterface $commandBus,
        private TotpServiceInterface $totpService,
        private JwtTokenManagerInterface $jwtTokenManager,
        private JWTEncoderInterface $jwtEncoder,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private UserRepositoryInterface $userRepository,
        private RememberDeviceService $rememberDeviceService,
        private CacheInterface $cache,
    ) {
    }

    /**
     * Start 2FA enrollment: generate secret, store on user, return QR + backup codes.
     */
    #[Route('/setup', name: 'setup', methods: ['POST'])]
    public function setup(#[CurrentUser] SecurityUser $securityUser): JsonResponse
    {
        $plainSecret = $this->totpService->generateSecret();
        $encryptedSecret = $this->totpService->encryptSecret($plainSecret);
        $backupCodes = $this->totpService->generateBackupCodes();

        $this->commandBus->dispatch(new EnableTwoFactorCommand(
            userId: $securityUser->getId(),
            encryptedSecret: $encryptedSecret,
            hashedBackupCodes: $backupCodes['hashed'],
        ));

        $qrCodeSvg = $this->totpService->generateQrCodeSvg($plainSecret, $securityUser->getUserIdentifier());

        return new JsonResponse(new TwoFactorSetupDTO(
            qrCodeSvg: $qrCodeSvg,
            secret: $plainSecret,
            backupCodes: $backupCodes['plain'],
        ));
    }

    /**
     * Confirm 2FA enrollment with a valid TOTP code from authenticator app.
     */
    #[Route('/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(Request $request, #[CurrentUser] SecurityUser $securityUser): JsonResponse
    {
        $data = $this->decodeJson($request);
        $code = $data['code'] ?? '';

        if ('' === $code) {
            throw new BadRequestHttpException('TOTP code is required.');
        }

        $this->commandBus->dispatch(new ConfirmTwoFactorCommand(
            userId: $securityUser->getId(),
            code: $code,
        ));

        return new JsonResponse(['message' => 'Two-factor authentication enabled.']);
    }

    /**
     * Verify TOTP code during login (requires partial JWT).
     * PUBLIC_ACCESS — partial JWT is validated manually.
     */
    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $code = $data['code'] ?? '';
        $rememberDevice = $data['remember_device'] ?? false;

        if ('' === $code) {
            throw new BadRequestHttpException('TOTP code is required.');
        }

        // Extract and validate partial JWT
        $partialToken = $this->extractBearerToken($request);
        $payload = $this->decodePartialToken($partialToken);

        $userId = $payload['user_id'] ?? null;
        $email = $payload['username'] ?? null;

        if (null === $userId || null === $email) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid partial token.');
        }

        // Rate limiting: max 5 attempts per token
        $tokenHash = hash('sha256', $partialToken);
        $cacheKey = 'two_fa_attempts_' . str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $tokenHash);

        /** @var int $attempts */
        $attempts = $this->cache->get($cacheKey, static fn (): int => 0);

        if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
            throw new TooManyRequestsHttpException(
                self::VERIFY_ATTEMPTS_TTL,
                'Too many 2FA verification attempts. Request a new login.',
            );
        }

        // Increment attempts (delete and re-set with TTL)
        $this->cache->delete($cacheKey);
        $newAttempts = $attempts + 1;
        $this->cache->get($cacheKey, static fn (): int => $newAttempts);

        // Load user and verify TOTP or backup code
        $user = $this->userRepository->findById(UserId::fromString($userId));

        if (null === $user || !$user->isTotpEnabled()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        $encryptedSecret = $user->totpSecret();

        if (null === $encryptedSecret) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        $plainSecret = $this->totpService->decryptSecret($encryptedSecret);
        $verified = $this->totpService->verifyCode($plainSecret, $code);

        if (!$verified) {
            // Try backup code
            $backupCodes = $user->backupCodes();

            if (null !== $backupCodes) {
                $backupIndex = $this->totpService->verifyBackupCode($code, $backupCodes);

                if (false !== $backupIndex) {
                    $user->consumeBackupCode($backupIndex);
                    $this->userRepository->save($user);
                    $verified = true;
                }
            }
        }

        if (!$verified) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid TOTP code.');
        }

        // Clear rate limit on success
        $this->cache->delete($cacheKey);

        // Issue full JWT tokens
        $accessToken = $this->jwtTokenManager->create(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            roles: $user->roles(),
        );

        $refreshToken = $this->refreshTokenManager->generate(
            userId: $user->id()->value(),
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        );

        $response = new JsonResponse([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        // Set remember device cookie if requested
        if (true === $rememberDevice) {
            $userAgent = $request->headers->get('User-Agent', '');
            $deviceToken = $this->rememberDeviceService->createDeviceToken($userId, $userAgent);

            $response->headers->setCookie(Cookie::create(
                'remember_2fa',
                $deviceToken,
                time() + (self::REMEMBER_DEVICE_DAYS * 86400),
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_STRICT,
            ));
        }

        return $response;
    }

    /**
     * Disable 2FA (requires valid TOTP code for confirmation).
     */
    #[Route('/disable', name: 'disable', methods: ['POST'])]
    public function disable(Request $request, #[CurrentUser] SecurityUser $securityUser): JsonResponse
    {
        $data = $this->decodeJson($request);
        $code = $data['code'] ?? '';

        if ('' === $code) {
            throw new BadRequestHttpException('TOTP code is required.');
        }

        $this->commandBus->dispatch(new DisableTwoFactorCommand(
            userId: $securityUser->getId(),
            code: $code,
        ));

        return new JsonResponse(['message' => 'Two-factor authentication disabled.']);
    }

    private function extractBearerToken(Request $request): string
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new UnauthorizedHttpException('Bearer', 'Missing or invalid Authorization header.');
        }

        return substr($authHeader, 7);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePartialToken(string $token): array
    {
        try {
            /** @var array<string, mixed> $payload */
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Exception) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired partial token.');
        }

        if (!isset($payload['2fa_required']) || true !== $payload['2fa_required']) {
            throw new AccessDeniedHttpException('Token is not a 2FA challenge token.');
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        /** @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
