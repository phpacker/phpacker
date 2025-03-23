<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Support\AssetManager;

trait InteractsWithAssetManager
{
    protected function assetManager(): AssetManager
    {
        return new AssetManager($this->repository);
    }
}
