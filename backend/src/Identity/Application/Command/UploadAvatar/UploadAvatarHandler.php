<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\UploadAvatar;

use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UploadAvatarHandler
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    /** @var array<string, string> */
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    public function __invoke(UploadAvatarCommand $command): void
    {
        // Validate file size
        if (strlen($command->fileContent) > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('File exceeds 5 MB limit.');
        }

        // Validate MIME type via finfo_buffer (not client-reported MIME)
        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($command->fileContent);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, GIF, WebP.');
        }

        $extension = self::MIME_TO_EXTENSION[$detectedMime];
        $uuid = bin2hex(random_bytes(16));
        $path = \sprintf('avatars/%s/%s.%s', $command->userId, $uuid, $extension);

        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        // Delete old avatar if one exists
        if (null !== $user->avatarPath()) {
            $this->avatarStorage->delete($user->avatarPath());
        }

        $this->avatarStorage->upload($path, $command->fileContent, $detectedMime);

        $user->setAvatarPath($path);

        $this->userRepository->save($user);
    }
}
