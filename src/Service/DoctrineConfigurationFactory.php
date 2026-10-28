<?php

declare(strict_types=1);

namespace Monarc\Core\Service;

use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use DoctrineORMModule\Options\Configuration as DoctrineORMModuleConfiguration;
use DoctrineORMModule\Service\DBALConfigurationFactory;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use UnexpectedValueException;

use function is_string;
use function method_exists;
use function sprintf;

/**
 * PHP 8.4/Doctrine Module 6 configuration factory.
 *
 * Doctrine ORM Module still invokes Doctrine Cache 1.x's deprecated
 * set*CacheImpl methods. MONARC supplies PSR-6 pools, as required by Doctrine
 * Cache 2, and configures them through Doctrine ORM's current API instead.
 */
final class DoctrineConfigurationFactory extends DBALConfigurationFactory
{
    public function __construct()
    {
        parent::__construct('orm_default');
    }

    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null): Configuration
    {
        $prefix = 'doctrine.configuration.';
        if (str_starts_with($requestedName, $prefix)) {
            $this->name = substr($requestedName, strlen($prefix));
        }

        $options = $this->getOptions($serviceLocator);
        if (!$options instanceof DoctrineORMModuleConfiguration) {
            throw new RuntimeException('Invalid Doctrine ORM configuration options.');
        }

        $config = new Configuration();
        $config->setAutoGenerateProxyClasses($options->getGenerateProxies());
        $config->setProxyDir($options->getProxyDir());
        $config->setProxyNamespace($options->getProxyNamespace());
        $config->setEntityNamespaces($options->getEntityNamespaces());
        $config->setCustomDatetimeFunctions($options->getDatetimeFunctions());
        $config->setCustomStringFunctions($options->getStringFunctions());
        $config->setCustomNumericFunctions($options->getNumericFunctions());
        $config->setClassMetadataFactoryName($options->getClassMetadataFactoryName());

        foreach ($options->getNamedQueries() as $name => $query) {
            $config->addNamedQuery($name, $query);
        }
        foreach ($options->getNamedNativeQueries() as $name => $query) {
            $config->addNamedNativeQuery($name, $query['sql'], new $query['rsm']());
        }
        foreach ($options->getCustomHydrationModes() as $modeName => $hydrator) {
            $config->addCustomHydrationMode($modeName, $hydrator);
        }
        foreach ($options->getFilters() as $name => $class) {
            $config->addFilter($name, $class);
        }

        $config->setMetadataCache($serviceLocator->get($options->getMetadataCache()));
        $config->setQueryCache($serviceLocator->get($options->getQueryCache()));
        $config->setResultCache($serviceLocator->get($options->getResultCache()));
        $config->setHydrationCache($serviceLocator->get($options->getHydrationCache()));
        $config->setMetadataDriverImpl($serviceLocator->get($options->getDriver()));

        $this->configureStrategies($serviceLocator, $config, $options);
        $this->configureSecondLevelCache($config, $options);

        $className = $options->getDefaultRepositoryClassName();
        if ($className) {
            $config->setDefaultRepositoryClassName($className);
        }

        $this->configureDbal($serviceLocator, $config, $options);

        return $config;
    }

    private function configureStrategies(
        ContainerInterface $serviceLocator,
        Configuration $config,
        DoctrineORMModuleConfiguration $options
    ): void {
        $namingStrategy = $options->getNamingStrategy();
        if ($namingStrategy) {
            $config->setNamingStrategy(is_string($namingStrategy)
                ? $this->getService($serviceLocator, $namingStrategy, 'Naming strategy')
                : $namingStrategy);
        }

        $quoteStrategy = $options->getQuoteStrategy();
        if ($quoteStrategy) {
            $config->setQuoteStrategy(is_string($quoteStrategy)
                ? $this->getService($serviceLocator, $quoteStrategy, 'Quote strategy')
                : $quoteStrategy);
        }

        $repositoryFactory = $options->getRepositoryFactory();
        if ($repositoryFactory) {
            $config->setRepositoryFactory(is_string($repositoryFactory)
                ? $this->getService($serviceLocator, $repositoryFactory, 'Repository factory')
                : $repositoryFactory);
        }

        $entityListenerResolver = $options->getEntityListenerResolver();
        if ($entityListenerResolver) {
            $config->setEntityListenerResolver($entityListenerResolver instanceof EntityListenerResolver
                ? $entityListenerResolver
                : $serviceLocator->get($entityListenerResolver));
        }
    }

    private function configureSecondLevelCache(Configuration $config, DoctrineORMModuleConfiguration $options): void
    {
        $secondLevelCache = $options->getSecondLevelCache();
        if (!$secondLevelCache->isEnabled()) {
            return;
        }

        $regionsConfig = new RegionsConfiguration(
            $secondLevelCache->getDefaultLifetime(),
            $secondLevelCache->getDefaultLockLifetime()
        );
        foreach ($secondLevelCache->getRegions() as $regionName => $regionConfig) {
            if (isset($regionConfig['lifetime'])) {
                $regionsConfig->setLifetime($regionName, $regionConfig['lifetime']);
            }
            if (isset($regionConfig['lock_lifetime'])) {
                $regionsConfig->setLockLifetime($regionName, $regionConfig['lock_lifetime']);
            }
        }

        $cacheFactory = new DefaultCacheFactory($regionsConfig, $config->getResultCache());
        $cacheFactory->setFileLockRegionDirectory($secondLevelCache->getFileLockRegionDirectory());
        $cacheConfiguration = new CacheConfiguration();
        $cacheConfiguration->setCacheFactory($cacheFactory);
        $cacheConfiguration->setRegionsConfiguration($regionsConfig);
        $config->setSecondLevelCacheEnabled();
        $config->setSecondLevelCacheConfiguration($cacheConfiguration);
    }

    private function configureDbal(
        ContainerInterface $serviceLocator,
        Configuration $config,
        DoctrineORMModuleConfiguration $options
    ): void {
        $sqlLogger = $options->getSQLLogger();
        if (is_string($sqlLogger) && $serviceLocator->has($sqlLogger)) {
            $sqlLogger = $serviceLocator->get($sqlLogger);
        }
        $config->setSQLLogger($sqlLogger);

        if (method_exists($config, 'setMiddlewares')) {
            $middlewares = [];
            foreach ($options->getMiddlewares() as $middlewareName) {
                if (!is_string($middlewareName) || !$serviceLocator->has($middlewareName)) {
                    throw new InvalidArgumentException('Middleware not exists');
                }
                $middleware = $serviceLocator->get($middlewareName);
                if (!$middleware instanceof Middleware) {
                    throw new UnexpectedValueException(sprintf('Invalid middleware with %s name. %s expected.', $middlewareName, Middleware::class));
                }
                $middlewares[] = $middleware;
            }
            $config->setMiddlewares($middlewares);
        }

        foreach ($options->getTypes() as $name => $class) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $class);
            } else {
                Type::addType($name, $class);
            }
        }
    }

    private function getService(ContainerInterface $serviceLocator, string $name, string $type): object
    {
        if (!$serviceLocator->has($name)) {
            throw new InvalidArgumentException(sprintf('%s "%s" not found', $type, $name));
        }

        return $serviceLocator->get($name);
    }
}
