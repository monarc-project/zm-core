<?php
namespace MonarcCore\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractControllerFactory implements FactoryInterface
{
    protected $serviceName;

    public function createService(ServiceLocatorInterface $serviceLocator){
        $c = substr(get_class($this),0,-7);
        if(class_exists($c)){
            $fn = $this->getServiceName();
            if(empty($fn)){
                return new $c();    
            }elseif(is_array($fn)){
                $sl = $serviceLocator->getServiceLocator();
                $sls = array();
                foreach ($fn as $k => $v) {
                    $sls[$k] = $sl->get($v);
                }
                return new $c($sls);
            }else{
                return new $c($serviceLocator->getServiceLocator()->get($fn));
            }
        }else{
            return false;
        }
    }

    public function getServiceName(){
        return $this->serviceName;
    }
}
