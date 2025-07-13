<?php

namespace PHPacker\PHPacker\Support;

use Symfony\Component\Filesystem\Path;
use PHPacker\PHPacker\Contracts\RemoteRepositoryService;
use PHPacker\PHPacker\Exceptions\RepositoryRequestException;

class GitHub implements RemoteRepositoryService
{
    public function __construct(
        protected string $repository,
    ) {}

    public function releaseData(): ?array
    {
        return once(function () {
            $url = "https://api.github.com/repos/{$this->repository}/releases/latest";
            $options = [
                'http' => [
                    'header' => ['User-Agent: PHPacker'],
                ],
            ];

            if ($_ENV['GITHUB_TOKEN'] ?? false) {
                $options['http']['header'][] = 'Authorization: Bearer ' . $_ENV['GITHUB_TOKEN'];
            }

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new RepositoryRequestException("Failed to fetch release data for: {$this->repository}");
            }

            return json_decode($response, true);
        });
    }

    public function downloadReleaseAssets(string $destination): string
    {
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: PHPacker',
            ],
        ]);

        $zipPath = Path::join($destination, 'latest.zip');
        $downloadUrl = $this->releaseData()['zipball_url'];

        // Make sure zip file is present & empty
        file_put_contents($zipPath, '');

        // Open streams to resources
        $remoteStream = fopen($downloadUrl, 'r', context: $context);
        $localStream = fopen($zipPath, 'w');

        if ($remoteStream === false) {
            throw new RepositoryRequestException("Failed to open stream to release assets at '{$downloadUrl}'");
        }

        if ($localStream === false) {
            throw new RepositoryRequestException("Failed to open stream to '{$zipPath}'");
        }

        // Keep it simple
        try {
            if (! stream_copy_to_stream($remoteStream, $localStream)) {
                throw new RepositoryRequestException("Failed to copy stream to '{$zipPath}'");
            }

            return $zipPath;
        } finally {
            fclose($remoteStream);
            fclose($localStream);
        }
    }

    public function latestVersion(): ?string
    {
        $response = $this->releaseData();

        return $response['tag_name'] ?? null;
    }
}
