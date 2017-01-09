<?php
namespace MonarcCore\Storage;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthentificationFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator){
        $sa = new Authentication();
        $sa->setUserTokenTable($serviceLocator->get('\MonarcCore\Model\Table\UserTokenTable'));
        $sa->setConfig($serviceLocator->get('config'));
        return $sa;
    }
}
