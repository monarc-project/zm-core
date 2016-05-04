<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AssetServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $service = new AssetService();

        $modelService = $serviceLocator->get('\MonarcCore\Service\ModelService');
        $service->setModelService($modelService);

        return $service;
    }
}
