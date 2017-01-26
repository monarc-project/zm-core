<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Connected User Service Factory
 *
 * Class ConnectedUserServiceFactory
 * @package MonarcCore\Service
 */
class ConnectedUserServiceFactory implements FactoryInterface
{
    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ConnectedUserService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $uc = new ConnectedUserService();
        $request = $token = $serviceLocator->get('Request');
        if (!empty($request) && method_exists($request, 'getHeader')) {
            $token = $request->getHeader('token');
            if (!empty($token)) {
                $success = false;
                $dd = $serviceLocator->get('\MonarcCore\Storage\Authentication')->getItem($token->getFieldValue(), $success);
                if ($success) {
                    $uc->setConnectedUser($dd->get('user'));
                }
            }
        }
        return $uc;
    }
}