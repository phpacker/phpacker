<?php

namespace Tests;

use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public Filesystem $filesystem;

    protected function setup(): void
    {
        $this->filesystem = new Filesystem;
    }
}
