<?php
namespace MonarcCore\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use \MonarcCore\Controller\MailTesterController;

class MailTesterControllerFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MailTesterController($serviceLocator->getServiceLocator()->get('MonarcCore\Service\MailTesterService'));
    }
}
