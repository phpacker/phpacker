<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Filesystem\Path;

trait WithVersions
{
    use InteractsWithGitHub;

    protected function currentVersion(string $repositoryDir): ?string
    {
        return @file_get_contents(Path::join($repositoryDir, 'version.txt'));
    }

    protected function latestVersion(string $repository): ?string
    {
        $response = @$this->repositoryData($repository);

        return $response['tag_name'] ?? null;
    }
}
