<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Port;

interface FileStorageInterface
{
    /**
     * Upload file content and return the storage path.
     */
    public function upload(string $path, string $content, string $mimeType): void;

    /**
     * Get a temporary download URL for a file.
     */
    public function getUrl(string $path): string;

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): void;
}
