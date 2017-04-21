<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MailTesterServiceFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MailTesterService($serviceLocator);
    }
}

