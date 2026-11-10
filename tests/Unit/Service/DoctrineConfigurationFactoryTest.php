<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Persistence\Proxy;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Service\DoctrineCacheServiceFactory;
use Monarc\Core\Service\DoctrineConfigurationFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @coversNothing */
class DoctrineConfigurationFactoryTest extends TestCase
{
    /** @dataProvider proxyModes */
    public function testDevelopmentCanCreateProxiesWithoutFilesAndOtherModesRemainConfigured(
        string $environment,
        bool $enabled,
        int $expected,
        string $managerName
    ): void {
        $previous = getenv('APP_ENV');
        $blockedDirectory = tempnam(sys_get_temp_dir(), 'monarc-proxy-');
        $cache = (new DoctrineCacheServiceFactory())(new ServiceManager(), 'tests');
        $driver = new AnnotationDriver(new AnnotationReader(), [dirname(__DIR__, 3) . '/src/Entity']);
        $services = [
            'config' => ['doctrine' => ['configuration' => [$managerName => [
                'generate_proxies' => $enabled,
                'proxy_dir' => $blockedDirectory,
                'proxy_namespace' => 'MonarcTestProxies' . bin2hex(random_bytes(4)),
                'class_metadata_factory_name' => ClassMetadataFactory::class,
                'metadata_cache' => 'tests', 'query_cache' => 'tests',
                'result_cache' => 'tests', 'hydration_cache' => 'tests', 'driver' => 'tests',
            ]]]],
            'doctrine.cache.tests' => $cache,
            'doctrine.driver.tests' => $driver,
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(static fn ($name) => $services[$name]);

        try {
            putenv('APP_ENV=' . $environment);
            $config = (new DoctrineConfigurationFactory())($container, 'doctrine.configuration.' . $managerName);
            self::assertSame($expected, $config->getAutoGenerateProxyClasses());
            if ($expected === ProxyFactory::AUTOGENERATE_EVAL) {
                $manager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
                $reference = $manager->getReference(Guide::class, 42);
                self::assertInstanceOf(Proxy::class, $reference);
                self::assertSame(42, $reference->getId());
                self::assertFalse($reference->__isInitialized());
                self::assertFileExists($blockedDirectory);
                $manager->close();
            }
        } finally {
            putenv($previous === false ? 'APP_ENV' : 'APP_ENV=' . $previous);
            unlink($blockedDirectory);
        }
    }

    public static function proxyModes(): array
    {
        return [
            ['development', true, ProxyFactory::AUTOGENERATE_EVAL, 'orm_default'],
            ['development', true, ProxyFactory::AUTOGENERATE_EVAL, 'orm_cli'],
            ['development', false, ProxyFactory::AUTOGENERATE_NEVER, 'orm_default'],
            ['production', true, ProxyFactory::AUTOGENERATE_ALWAYS, 'orm_default'],
            ['production', false, ProxyFactory::AUTOGENERATE_NEVER, 'orm_cli'],
        ];
    }
}
