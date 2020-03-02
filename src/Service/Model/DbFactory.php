<?php

namespace Monarc\Core\Service\Model;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Db;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DbFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Db($container->get('doctrine.entitymanager.orm_default'));
    }
}
