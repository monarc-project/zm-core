<?php
namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

class ObjectServiceModelTable extends AbstractServiceModelTable
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = parent::createService($serviceLocator);
        if ($instance !== false) {
            $instance->setObjectObjectTable($serviceLocator->get('\MonarcCore\Model\Table\ObjectObjectTable'));
        }
        return $instance;
    }
}
