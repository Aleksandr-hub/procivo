<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\UploadAvatar;

use App\Shared\Application\Command\CommandInterface;

final readonly class UploadAvatarCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $fileContent,
        public string $originalName,
    ) {
    }
}
