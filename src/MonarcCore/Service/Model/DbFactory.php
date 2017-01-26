<?php

namespace MonarcCore\Service\Model;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DbFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new \MonarcCore\Model\Db($serviceLocator->get('doctrine.entitymanager.orm_default'));
    }
}
