<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monarc\Core\Storage\Authentication;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Connected User Service Factory
 *
 * Class ConnectedUserServiceFactory
 * @package Monarc\Core\Service
 */
class ConnectedUserServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $connectedUserService = new ConnectedUserService();
        $request = $container->get('Request');
        if (!empty($request) && method_exists($request, 'getHeader')) {
            $token = $request->getHeader('token');
            if (!empty($token)) {
                $success = false;
                $dd = $container->get(Authentication::class)->getItem($token->getFieldValue(), $success);
                if ($success) {
                    $connectedUserService->setConnectedUser($dd->get('user'));
                }
            }
        }

        return $connectedUserService;
    }
}
