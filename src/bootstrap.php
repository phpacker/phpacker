<?php

/**
 * This file is loaded both via composer & included in the build
 */
php_sapi_name() === 'micro'
    ? putenv('PHPACKER_ENV=production')
    : putenv('PHPACKER_ENV=local');
