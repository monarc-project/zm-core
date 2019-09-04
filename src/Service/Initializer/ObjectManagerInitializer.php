<?php

namespace Monarc\Core\Service\Initializer;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;

class ObjectManagerInitializer implements InitializerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof ObjectManagerAwareInterface) {
            $instance->setObjectManager($container->get('doctrine.entitymanager.orm_default'));
        }
    }
}
