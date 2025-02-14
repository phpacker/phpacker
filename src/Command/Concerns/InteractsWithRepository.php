<?php

namespace PHPacker\PHPacker\Command\Concerns;

trait InteractsWithRepository
{
    public function repositoryData(string $repository): ?array
    {
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
    }
}
