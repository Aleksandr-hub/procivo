<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

interface AvatarStorageInterface
{
    public function upload(string $path, string $content, string $mimeType): void;

    public function getUrl(string $path): string;

    public function delete(string $path): void;
}
