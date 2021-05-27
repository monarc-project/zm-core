<?php declare(strict_types=1);

namespace Monarc\FrontOffice\Model\Table\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CoreEntityManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container->get('doctrine.entitymanager.orm_default'));
    }
}
