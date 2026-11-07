<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

$application = getenv('MONARC_TEST_APP') ?: 'MonarcAppFO';
if (!in_array($application, ['MonarcAppFO', 'MonarcAppBO'], true)) {
    throw new RuntimeException('MONARC_TEST_APP must be MonarcAppFO or MonarcAppBO.');
}
$applicationDir = getenv('MONARC_TEST_APP_DIR') ?: dirname(__DIR__, 2) . '/' . $application;
require $applicationDir . '/vendor/autoload.php';
require_once __DIR__ . '/Runtime/MappingBaseline.php';
date_default_timezone_set('Europe/Luxembourg');
putenv('APPLICATION_ENV=testing');
putenv('APP_ENV=testing');
