<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThemeServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connectedUser = $serviceLocator->get('MonarcCore\Service\ConnectedUserService')->getConnectedUser();

        $service = new ThemeService();
        $service->setConnectedUser($connectedUser);

        return $service;
    }
}
