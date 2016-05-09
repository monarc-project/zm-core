<?php


namespace MonarcCore\Service\Initializer;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Created by PhpStorm.
 * User: jerome
 * Date: 03/05/2016
 * Time: 16:25
 */
class ObjectManagerInitializer implements InitializerInterface
{
    public function initialize($instance, ServiceLocatorInterface $serviceLocator)
    {
        if($instance instanceof ObjectManagerAwareInterface) {
            $instance->setObjectManager($serviceLocator->get('doctrine.entitymanager.orm_default'));
        }
    }


}