<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\TotpServiceInterface;
use Endroid\QrCode\Builder\Builder;
use OTPHP\TOTP;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class TotpService implements TotpServiceInterface
{
    private string $encryptionKey;
    private string $encryptionIv;

    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private string $appSecret,
    ) {
        $hash = hash('sha256', $this->appSecret);
        $this->encryptionKey = substr($hash, 0, 32);
        $this->encryptionIv = substr($hash, 0, 16);
    }

    public function generateSecret(): string
    {
        $totp = TOTP::generate();

        return $totp->getSecret();
    }

    public function encryptSecret(string $plainSecret): string
    {
        $encrypted = openssl_encrypt(
            $plainSecret,
            'aes-256-cbc',
            $this->encryptionKey,
            0,
            $this->encryptionIv,
        );

        if (false === $encrypted) {
            throw new \RuntimeException('Failed to encrypt TOTP secret.');
        }

        return $encrypted;
    }

    public function decryptSecret(string $encrypted): string
    {
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $this->encryptionKey,
            0,
            $this->encryptionIv,
        );

        if (false === $decrypted) {
            throw new \RuntimeException('Failed to decrypt TOTP secret.');
        }

        return $decrypted;
    }

    public function verifyCode(string $plainSecret, string $code): bool
    {
        $totp = TOTP::createFromSecret($plainSecret);

        return $totp->verify($code, null, 1);
    }

    public function generateQrCodeSvg(string $plainSecret, string $email): string
    {
        $totp = TOTP::createFromSecret($plainSecret);
        $totp->setLabel($email);
        $totp->setIssuer('Procivo');

        $builder = new Builder(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $totp->getProvisioningUri(),
            size: 300,
            margin: 10,
        );

        $result = $builder->build();

        return $result->getString();
    }

    /**
     * @return array{plain: list<string>, hashed: list<string>}
     */
    public function generateBackupCodes(): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < 8; ++$i) {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $plain[] = $code;
            $hashed[] = password_hash($code, \PASSWORD_BCRYPT);
        }

        return ['plain' => $plain, 'hashed' => $hashed];
    }

    public function verifyBackupCode(string $inputCode, array $hashedCodes): int|false
    {
        foreach ($hashedCodes as $index => $hash) {
            if (password_verify($inputCode, $hash)) {
                return $index;
            }
        }

        return false;
    }
}
