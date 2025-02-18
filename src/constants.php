<?php

use Symfony\Component\Filesystem\Path;

// Define APP_DATA constant
define('APP_DATA', match (PHP_OS_FAMILY) {
    'Darwin' => Path::join(getenv('HOME'), '.phpacker'),
    'Windows' => Path::join(getenv('LOCALAPPDATA'), 'phpacker'),
    default => Path::join(getenv('XDG_DATA_HOME') ?: Path::join(getenv('HOME'), '.phpacker'))
});
