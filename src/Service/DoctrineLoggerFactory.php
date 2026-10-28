<?php
namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monarc\Core\Log\SqlLogger;
use RuntimeException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * TODO: refactor the class, inject all the required things in the factory: config, environment.
 *
 * Class DoctrineLoggerFactory
 *
 * @package Monarc\Core\Service
 */
class DoctrineLoggerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     *
     * @return SqlLogger
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $env = getenv('APP_ENV') ?: 'production';
        $config = $container->get('Config');
        $enable = $config['monarc']['doctrineLog'] ?? false;
        if ($env !== 'production' && $enable) {
            $dataPath = './data/log/';
            $appConfDir = getenv('APP_CONF_DIR') ? getenv('APP_CONF_DIR') : '';
            if (!empty($appConfDir)) {
                $dataPath = $appConfDir.'/data/log/';
            }
            if (!is_dir($dataPath)) {
                if (!mkdir($dataPath) && !is_dir($dataPath)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $dataPath));
                }
            }

            $log = new Logger('doctrine');
            $log->pushHandler(new StreamHandler($dataPath.date('Y-m-d').'-doctrine.log', Level::Debug));

            $sqlLogger = new SqlLogger($log);
            $sqlLogger->enabled = true;

            return $sqlLogger;
        }

        $log = new Logger('doctrine');
        $sqlLogger = new SqlLogger($log);
        $sqlLogger->enabled = false;

        return $sqlLogger;
    }
}
