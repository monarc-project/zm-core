<?php declare(strict_types=1);

chdir(dirname(__DIR__));

date_default_timezone_set('UTC');

putenv('TESTS_DIR=' . dirname(__DIR__) . '/tests');
putenv('APPLICATION_ENV=testing');

require dirname(__DIR__) . '/vendor/autoload.php';
