<?php

/**
 * Remnant of an idea. Keep around for future reference
 */
$_phpacker_bootstrap = function () {

    php_sapi_name() === 'micro'
        ? putenv('PHPACKER_ENV=production')
        : putenv('PHPACKER_ENV=local');

};

$_phpacker_bootstrap();
