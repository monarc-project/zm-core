<?php
namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monarc\Core\Log\SqlLogger;
use RuntimeException;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
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
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
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

            $writer = new Stream($dataPath.date('Y-m-d').'-doctrine.log');

            $log = new Logger();
            $log->addWriter($writer);

            $sqlLogger = new SqlLogger($log);
            $sqlLogger->enabled = true;

            return $sqlLogger;
        }

        $log = new Logger();
        $sqlLogger = new SqlLogger($log);
        $sqlLogger->enabled = false;

        return $sqlLogger;
    }
}
