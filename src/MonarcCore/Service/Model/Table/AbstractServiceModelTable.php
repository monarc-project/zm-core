<?php

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractServiceModelTable implements FactoryInterface
{
    protected $dbService = '\MonarcCore\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator){
        $class = str_replace('Service\\', '', substr(get_class($this),0,-17)).'Table';
        if(class_exists($class)){
            $instance = new $class($serviceLocator->get($this->dbService));
            $instance->setConnectedUser($serviceLocator->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());

            return $instance;
        }else{
            return false;
        }
    }
}
