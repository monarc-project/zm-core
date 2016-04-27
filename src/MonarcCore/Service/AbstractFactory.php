<?php
/**
 * Created by PhpStorm.
 * User: jerome
 * Date: 27/04/2016
 * Time: 10:00
 */

namespace MonarcCore\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {

    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {

    }
}
