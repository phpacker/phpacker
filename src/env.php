<?php

if (php_sapi_name() === 'micro') {
    putenv('PHPACKER_ENV=production');
} else {
    putenv('PHPACKER_ENV=local');
}
