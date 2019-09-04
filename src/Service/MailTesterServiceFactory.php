<?php
namespace Monarc\Core\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MailTesterServiceFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MailTesterService($serviceLocator);
    }
}

