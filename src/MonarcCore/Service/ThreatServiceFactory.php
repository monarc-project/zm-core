<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThreatServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connectedUser = $serviceLocator->get('MonarcCore\Service\ConnectedUserService')->getConnectedUser();
        $modelService = $serviceLocator->get('\MonarcCore\Service\ModelService');
        $themeService = $serviceLocator->get('\MonarcCore\Service\ThemeService');

        $service = new ThreatService();
        $service->setConnectedUser($connectedUser);
        $service->setModelService($modelService);
        $service->setThemeService($themeService);

        return $service;
    }
}
