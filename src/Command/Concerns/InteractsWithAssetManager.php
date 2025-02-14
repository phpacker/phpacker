<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Services\AssetManager;

trait InteractsWithAssetManager
{
    protected function assetManager(): AssetManager
    {
        return new AssetManager($this->repositoryDir);
    }
}
