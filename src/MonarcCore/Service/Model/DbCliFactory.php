<?php

namespace MonarcCore\Service\Model;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DbCliFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator){
        try{
            $serviceLocator->get('doctrine.entitymanager.orm_cli')->getConnection()->connect();
            return new \MonarcCore\Model\Db($serviceLocator->get('doctrine.entitymanager.orm_cli'));
        }catch(\Exception $e){
            return new \MonarcCore\Model\Db($serviceLocator->get('doctrine.entitymanager.orm_default'));
        }
    }
}
