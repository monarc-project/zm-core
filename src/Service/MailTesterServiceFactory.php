<?php
namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MailTesterServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new MailTesterService($container->get('console'), $container->get(MailService::class));
    }
}

