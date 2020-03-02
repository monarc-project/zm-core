<?php

namespace Monarc\Core\Service\Model;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\DbCli;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DbCliFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new DbCli($container->get('doctrine.entitymanager.orm_cli'));
    }
}
