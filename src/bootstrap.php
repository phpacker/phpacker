<?php

/**
 * This file is loaded both via composer & included in the build
 * so it's always invoked regardless of context
 *
 * - as long as the build source includes the composer autoloader
 */
php_sapi_name() === 'micro'
    ? putenv('PHPACKER_ENV=production')
    : putenv('PHPACKER_ENV=local');
