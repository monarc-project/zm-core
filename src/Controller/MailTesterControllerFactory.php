<?php
namespace Monarc\Core\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use \Monarc\Core\Controller\MailTesterController;

class MailTesterControllerFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MailTesterController($serviceLocator->getServiceLocator()->get('Monarc\Core\Service\MailTesterService'));
    }
}
