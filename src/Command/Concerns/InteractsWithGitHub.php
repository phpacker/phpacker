<?php

namespace PHPacker\PHPacker\Command\Concerns;

trait InteractsWithGitHub
{
    protected function releaseData(string $repository): ?array
    {
        return once(function () use ($repository) {
            $url = "https://api.github.com/repos/{$repository}/releases/latest";
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

    protected function downloadReleaseAssets(string $repository)
    {
        // Download file with file_get_contents
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: PHPacker',
            ],
        ]);

        return file_get_contents($this->releaseData($repository)['zipball_url'], false, $context);
    }
}
