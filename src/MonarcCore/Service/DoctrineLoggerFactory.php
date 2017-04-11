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
        $config = $serviceLocator->get('Config');
        $enable = isset($config['monarc']['doctrineLog'])?$config['monarc']['doctrineLog']:false;
        if($env != 'production' && $enable){
            $datapath = './data/log/';
            $appconfdir = getenv('APP_CONF_DIR') ? getenv('APP_CONF_DIR') : '';
            if( ! empty($appconfdir) ){
                $datapath = $appconfdir.'/data/log/';
            }
            if(!is_dir($datapath)){
                mkdir($datapath);
            }
            $writer = new \Zend\Log\Writer\Stream($datapath.date('Y-m-d').'-doctrine.log');

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