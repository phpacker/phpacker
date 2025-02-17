<?php

namespace PHPacker\PHPacker\Support;

use PHPacker\PHPacker\Support\Config\ConfigRepository;

use function Laravel\Prompts\info;

class Combine
{
    public static function build(string $platform, string $arch, ConfigRepository $config)
    {
        //
        info("Building for {$platform}-{$arch}");
    }
}
