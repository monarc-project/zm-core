<?php
namespace MonarcCore\Adapter;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthentificationFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator){
        $aa = new Authentication();
        $aa->setUserTable($serviceLocator->get('\MonarcCore\Model\Table\UserTable'));
        $aa->setSecurity($serviceLocator->get('\MonarcCore\Service\SecurityService'));
        return $aa;
    }
}
