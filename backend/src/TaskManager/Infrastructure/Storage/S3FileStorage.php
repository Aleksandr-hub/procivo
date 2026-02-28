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
    private readonly S3Client $publicClient;
    private readonly string $bucket;

    public function __construct(
        string $endpoint,
        string $endpointPublic,
        string $region,
        string $bucket,
        string $accessKeyId,
        string $secretAccessKey,
    ) {
        $this->bucket = $bucket;

        $clientConfig = [
            'region' => $region,
            'version' => 'latest',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ];

        $this->client = new S3Client([...$clientConfig, 'endpoint' => $endpoint]);
        $this->publicClient = new S3Client([...$clientConfig, 'endpoint' => $endpointPublic]);

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
        // Generate a pre-signed URL using the public endpoint (accessible from browser)
        $command = $this->publicClient->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $presignedRequest = $this->publicClient->createPresignedRequest($command, '+1 hour');

        return (string) $presignedRequest->getUri();
    }

    public function delete(string $path): void
    {
        $this->filesystem->delete($path);
    }
}
