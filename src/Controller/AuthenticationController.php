<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Service\AuthenticationService;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

/**
 * Authentication Controller
 *
 * Class AuthenticationController
 * @package Monarc\Core\Controller
 */
class AuthenticationController extends AbstractRestfulController
{
    /** @var AuthenticationService */
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @inheritdoc
     */
    public function create($data)
    {
        $t = null;
        $uid = null;
        $language = null;

        // If the authentication is successful, return 200 with a token and the user ID. Otherwise, return an HTTP
        // error 405 so that the frontend can process accordingly. Remember that 401 is a reserved code that will
        // reset the authentication token and will redirect the user to the login page.
        if ($this->authenticationService->authenticate($data, $t, $uid, $language)) {
            $this->getResponse()->setStatusCode(200);

            return new JsonModel(['token' => $t, 'uid' => $uid, 'language' => $language]);
        } else {
            $this->getResponse()->setStatusCode(405);

            return new JsonModel([]);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteList($data)
    {
        $token = $this->getRequest()->getHeader('token');
        $this->authenticationService->logout([
            'token' => $token->getFieldValue()
        ]);

        return new JsonModel(array());
    }
}