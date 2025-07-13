<?php

(static function (): void {
    if (file_exists($autoload = __DIR__ . '/../../../autoload.php')) {
        // Is installed via Composer
        include_once $autoload;

        return;
    }

    if (file_exists($autoload = __DIR__ . '/../vendor/autoload.php')) {
        // Is installed locally
        include_once $autoload;

        return;
    }

    throw new RuntimeException('Unable to find the Composer autoloader.');
})();
