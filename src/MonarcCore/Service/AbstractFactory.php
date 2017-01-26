<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Factory
 *
 * Class AbstractFactory
 * @package MonarcCore\Service
 */
class AbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        //return false;//(substr($requestedName,0, strlen('monarc.service.'))) == 'monarc.service.';
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
    }
}
