<?php

namespace PHPacker\PHPacker\Contracts;

interface RemoteRepositoryService
{
    /** @return array{ tag_name: string, zipball_url: string, assets: array } */
    public function releaseData(): ?array;

    public function downloadReleaseAssets(string $destination): string;
}
