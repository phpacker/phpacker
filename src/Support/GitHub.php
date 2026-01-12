<?php

namespace PHPacker\PHPacker\Support;

use Symfony\Component\Filesystem\Path;
use PHPacker\PHPacker\Contracts\RemoteRepositoryService;
use PHPacker\PHPacker\Exceptions\RepositoryRequestException;

class GitHub implements RemoteRepositoryService
{
    /**
     * @param  string  $repository  The GitHub repository (e.g., "vendor/repo")
     */
    public function __construct(
        protected string $repository,
    ) {}

    /**
     * Fetch the latest release data from the GitHub API using cURL.
     *
     * @throws RepositoryRequestException
     */
    public function releaseData(): ?array
    {
        return once(function () {
            $url = "https://api.github.com/repos/{$this->repository}/releases/latest";

            $ch = curl_init($url);

            $headers = [
                'User-Agent: PHPacker',
                'Accept: application/vnd.github.v3+json',
            ];

            if ($_ENV['GITHUB_TOKEN'] ?? false) {
                $headers[] = 'Authorization: Bearer ' . $_ENV['GITHUB_TOKEN'];
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FAILONERROR => false, // We want to capture the body on 4xx/5xx
            ]);

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Handle connection-level failures (DNS, SSL, Timeout)
            if ($response === false) {
                throw new RepositoryRequestException("Failed to connect to GitHub for '{$this->repository}': {$error}");
            }

            $data = json_decode($response, true);

            // Handle API-level failures (404, 403 Rate Limit, etc.)
            if ($statusCode >= 400) {
                $errorMessage = $data['message'] ?? 'No error message provided by API';
                $docsUrl = isset($data['documentation_url']) ? " (See: {$data['documentation_url']})" : '';

                throw new RepositoryRequestException(
                    "GitHub API Error [{$statusCode}] for '{$this->repository}': {$errorMessage}{$docsUrl}"
                );
            }

            return $data;
        });
    }

    /**
     * Download the source code zipball for the latest release.
     *
     * @return string Path to the downloaded zip file
     *
     * @throws RepositoryRequestException
     */
    public function downloadReleaseAssets(string $destination): string
    {
        $release = $this->releaseData();

        if (! isset($release['zipball_url'])) {
            throw new RepositoryRequestException("Release data for '{$this->repository}' is missing the 'zipball_url'.");
        }

        $zipPath = Path::join($destination, 'latest.zip');
        $downloadUrl = $release['zipball_url'];

        // Ensure the directory exists
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $fp = fopen($zipPath, 'w+');
        if ($fp === false) {
            throw new RepositoryRequestException("Failed to create local file at '{$zipPath}'");
        }

        $ch = curl_init($downloadUrl);

        $headers = [
            'User-Agent: PHPacker',
        ];

        if ($_ENV['GITHUB_TOKEN'] ?? false) {
            $headers[] = 'Authorization: Bearer ' . $_ENV['GITHUB_TOKEN'];
        }

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 300, // Longer timeout for downloads
            CURLOPT_FILE => $fp,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $success = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (! $success || $statusCode >= 400) {
            $reason = $error ?: "HTTP Status {$statusCode}";
            throw new RepositoryRequestException("Failed to download release assets from '{$downloadUrl}': {$reason}");
        }

        return $zipPath;
    }

    /**
     * Get the latest version tag name.
     */
    public function latestVersion(): ?string
    {
        $response = $this->releaseData();

        return $response['tag_name'] ?? null;
    }
}
