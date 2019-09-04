<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract Controller Factory
 *
 * Class AbstractControllerFactory
 * @package Monarc\Core\Controller
 */
abstract class AbstractControllerFactory implements FactoryInterface
{
    /**
     * The service name to load for associated controller
     * @var string
     */
    protected $serviceName;

    /**
     * @inheritdoc
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $class = substr(get_class($this),0,-7);

        if(class_exists($class)){
            $service = $this->getServiceName();
            if (empty($service)) {
                return new $class();
            } elseif (is_array($service)) {
                $sm = $serviceLocator->getServiceLocator();
                $sls = array();
                foreach ($service as $key => $value) {
                    $sls[$key] = $sm->get($value);
                }
                return new $class($sls);
            } else {
                return new $class($serviceLocator->getServiceLocator()->get($service));
            }
        } else {
            return false;
        }
    }

    /**
     * Return the service name property
     * @return string The service name
     */
    public function getServiceName(){
        return $this->serviceName;
    }
}
