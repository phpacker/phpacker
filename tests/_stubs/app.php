#!/usr/bin/env php

<?php

echo 'Hello World!' . PHP_EOL;

echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;

echo 'PHPACKER_ENV: ' . (getenv('PHPACKER_ENV') ?? 'unset') . PHP_EOL;
echo 'SAPI: ' . php_sapi_name();

exit(0);
