<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

// Run from an initialized local application directory, after its image rebuild.
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Laminas\Mvc\Application;
use Monarc\Core\Entity\Asset;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\User;
use Monarc\Core\InputFormatter\Asset\GetAssetsInputFormatter;
use Monarc\Core\Service\AssetService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\GuideService;
use Monarc\Core\Tests\Runtime\MappingBaseline;

require_once __DIR__ . '/Runtime/MappingBaseline.php';

$phase = 'environment';
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    return false;
});
try {
    if (getenv('APP_ENV') !== 'development') {
        throw new RuntimeException('Run only in the local development application.');
    }
    if (PHP_VERSION_ID < 80427 || PHP_VERSION_ID >= 80500) {
        throw new RuntimeException('PHP >=8.4.27 <8.5 is required.');
    }
    $appDir = getcwd();
    require $appDir . '/vendor/autoload.php';
    $isFrontOffice = Composer\InstalledVersions::isInstalled('monarc/frontoffice');
    $office = $isFrontOffice ? 'FrontOffice' : 'BackOffice';
    $module = $isFrontOffice ? 'zm-client' : 'zm-backoffice';
    $controller = $isFrontOffice
        ? Monarc\FrontOffice\Controller\ApiAnrAssetsController::class
        : Monarc\BackOffice\Controller\ApiAssetsController::class;
    $sources = [Asset::class => 'zm-core', Guide::class => 'zm-core', $controller => $module];
    $phase = 'local sources';
    foreach ($sources as $class => $repo) {
        $actual = realpath((new ReflectionClass($class))->getFileName());
        $expected = realpath(dirname($appDir) . '/' . $repo . '/src');
        if ($expected === false || !str_starts_with($actual, $expected . '/')) {
            throw new RuntimeException('A module does not resolve to its mounted sibling source.');
        }
    }
    $dependency = realpath((new ReflectionClass(EntityManagerInterface::class))->getFileName());
    if (!str_starts_with($dependency, realpath($appDir . '/vendor') . '/')) {
        throw new RuntimeException('Doctrine does not resolve through this application vendor directory.');
    }
    $phase = 'application bootstrap';
    $app = Application::init(require $appDir . '/config/application.config.php');
    $container = $app->getServiceManager();
    $metadataCounts = [];
    $mappingDiagnostics = [];
    $mappingBaseline = json_decode(
        file_get_contents(__DIR__ . '/Fixture/php84-mapping-baseline.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $strictMapping = in_array('--strict-mapping', $argv, true);
    foreach (['orm_default', 'orm_cli'] as $name) {
        $phase = $name . ' database';
        $manager = $container->get('doctrine.entitymanager.' . $name);
        if ((int)$manager->getConnection()->fetchOne('SELECT 1') !== 1) {
            throw new RuntimeException('Database read probe failed.');
        }
        $phase = $name . ' metadata';
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        if ($metadata === []) {
            throw new RuntimeException('No Doctrine metadata was loaded.');
        }
        $mappingErrors = (new SchemaValidator($manager))->validateMapping();
        $knownErrors = $mappingBaseline['offices'][$office][$name]['errors'];
        $unexpectedErrors = MappingBaseline::unexpectedErrors($mappingErrors, $strictMapping ? [] : $knownErrors);
        if ($unexpectedErrors !== []) {
            $diagnostic = json_encode(['unexpectedMappingErrors' => $unexpectedErrors], JSON_THROW_ON_ERROR);
            fwrite(STDERR, $diagnostic . PHP_EOL);
            throw new RuntimeException('Doctrine mapping validation failed.');
        }
        $mappingDiagnostics[$name] = array_sum(array_map('count', $mappingErrors));
        $phase = $name . ' proxies';
        $manager->getProxyFactory()->generateProxyClasses($metadata);
        $metadataCounts[$name] = count($metadata);
    }
    $phase = 'service container';
    // An unsaved identity initializes services that require a connected user.
    // No login, user token, persisted user or database write is involved.
    $container->get(ConnectedUserService::class)->setConnectedUser(new User([
        'firstname' => 'Local',
        'lastname' => 'Readiness',
        'email' => 'readiness@example.invalid',
        'creator' => 'PHP84-01',
        'role' => [],
    ]));
    $container->get(GetAssetsInputFormatter::class);
    $container->get('ControllerManager')->get($controller);
    $phase = 'modern asset read';
    $params = $container->get(GetAssetsInputFormatter::class)->format([]);
    $assetCount = $container->get(AssetService::class)->getCount($params);
    $phase = 'modern guide read';
    $guideParams = (new Monarc\Core\InputFormatter\Guide\GetGuidesInputFormatter())->format(['limit' => 1]);
    $guides = $container->get(GuideService::class)->getList($guideParams);
    $guideChecks = [];
    if (in_array('--guides', $argv, true)) {
        $phase = 'guide workflows';
        require_once __DIR__ . '/Runtime/GuideRuntimeChecks.php';
        $guideChecks = Monarc\Core\Tests\Runtime\GuideRuntimeChecks::run($container, $office, $phase);
    }
    $questionChecks = [];
    if (in_array('--questions', $argv, true)) {
        $phase = 'question workflows';
        require_once __DIR__ . '/Runtime/QuestionRuntimeChecks.php';
        $questionChecks = Monarc\Core\Tests\Runtime\QuestionRuntimeChecks::run($container, $office, $phase);
    }
    echo json_encode([
        'office' => $office,
        'php' => PHP_VERSION,
        'sources' => array_map(static function (string $class): string {
            return realpath((new ReflectionClass($class))->getFileName());
        }, array_keys($sources)),
        'metadata' => $metadataCounts,
        'existingMappingDiagnostics' => $mappingDiagnostics,
        'strictMapping' => $strictMapping,
        'assetCount' => $assetCount,
        'guideCount' => count($guides),
        'guideChecks' => $guideChecks,
        'questionChecks' => $questionChecks,
        'result' => 'passed',
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    // Do not serialize application configuration or connection exception traces.
    fwrite(STDERR, 'PHP84-01 failed at ' . $phase . ': ' . get_class($error)
        . ' in ' . $error->getFile() . ':' . $error->getLine() . PHP_EOL);
    if ($error instanceof Doctrine\DBAL\Exception\DriverException) {
        fwrite(STDERR, 'Database SQLSTATE: ' . $error->getSQLState()
            . '; driver code: ' . $error->getPrevious()->getCode() . PHP_EOL);
    }
    exit(1);
}
