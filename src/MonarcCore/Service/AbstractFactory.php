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
        //return false;//(substr($requestedName,0, strlen('monarc.service.'))) == 'monarc.service.';
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {

    }
}
