<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ModelServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connectedUser = $serviceLocator->get('MonarcCore\Service\ConnectedUserService')->getConnectedUser();

        $service = new ModelService();
        $service->setConnectedUser($connectedUser);

        return $service;
    }
}
