<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Doctrine Cache Service Factory
 *
 * Class DoctrineLoggerFactory
 * @package MonarcCore\Service
 */
class DoctrineLoggerFactory implements FactoryInterface
{
    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\Common\Cache\ArrayCache|\Doctrine\Common\Cache\ChainCache
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $env = getenv('APP_ENV') ?: 'production';
        $conf = $serviceLocator->get('Config');
        $enable = isset($config['monarc']['doctrineLog'])?$config['monarc']['doctrineLog']:false;
        if($env != 'production' && $enable){
            if(!is_dir('data/log')){
                mkdir('data/log');
            }
            $writer = new \Zend\Log\Writer\Stream('data/log/'.date('Y-m-d').'-doctrine.log');

            $log = new \Zend\Log\Logger();
            $log->addWriter($writer);

            $sqllog = new \MonarcCore\Log\SqlLogger($log);
            $sqllog->enabled = true;
            return $sqllog;
        }else{
            $log = new \Zend\Log\Logger();
            $sqllog = new \MonarcCore\Log\SqlLogger($log);
            $sqllog->enabled = false;
            return $sqllog;
        }
    }
}