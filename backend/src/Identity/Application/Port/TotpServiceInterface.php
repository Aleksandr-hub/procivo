<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

interface TotpServiceInterface
{
    /**
     * Generate a new TOTP secret (plain text).
     */
    public function generateSecret(): string;

    /**
     * Encrypt a plain secret for storage.
     */
    public function encryptSecret(string $plainSecret): string;

    /**
     * Decrypt an encrypted secret for verification.
     */
    public function decryptSecret(string $encrypted): string;

    /**
     * Verify a TOTP code against a plain secret.
     */
    public function verifyCode(string $plainSecret, string $code): bool;

    /**
     * Generate a QR code SVG for TOTP enrollment.
     */
    public function generateQrCodeSvg(string $plainSecret, string $email): string;

    /**
     * Generate backup codes.
     *
     * @return array{plain: list<string>, hashed: list<string>}
     */
    public function generateBackupCodes(): array;

    /**
     * Verify an input code against hashed backup codes.
     *
     * @param list<string> $hashedCodes
     *
     * @return int|false index of matched code, or false
     */
    public function verifyBackupCode(string $inputCode, array $hashedCodes): int|false;
}
