<?php

namespace PHPacker\PHPacker\Services;

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
                    'header' => 'User-Agent: PHPacker',
                ],
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
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
            $bytesCopied = stream_copy_to_stream($remoteStream, $localStream);
            if ($bytesCopied === false) {
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
        $response = @$this->releaseData();

        return $response['tag_name'] ?? null;
    }
}
