<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Storage;

use App\TaskManager\Application\Port\FileStorageInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;

final class S3FileStorage implements FileStorageInterface
{
    private readonly FilesystemOperator $filesystem;
    private readonly S3Client $client;
    private readonly string $bucket;

    public function __construct(
        string $endpoint,
        string $region,
        string $bucket,
        string $accessKeyId,
        string $secretAccessKey,
    ) {
        $this->bucket = $bucket;

        $this->client = new S3Client([
            'region' => $region,
            'version' => 'latest',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);

        // Ensure bucket exists
        if (!$this->client->doesBucketExist($bucket)) {
            $this->client->createBucket(['Bucket' => $bucket]);
        }

        $adapter = new AwsS3V3Adapter($this->client, $bucket);
        $this->filesystem = new Filesystem($adapter);
    }

    public function upload(string $path, string $content, string $mimeType): void
    {
        $this->filesystem->write($path, $content, [
            'ContentType' => $mimeType,
        ]);
    }

    public function getUrl(string $path): string
    {
        // Generate a pre-signed URL valid for 1 hour
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $presignedRequest = $this->client->createPresignedRequest($command, '+1 hour');

        return (string) $presignedRequest->getUri();
    }

    public function delete(string $path): void
    {
        $this->filesystem->delete($path);
    }
}
