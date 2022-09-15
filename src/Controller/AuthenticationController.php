<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Model\Entity\User;
use Monarc\Core\Service\AuthenticationService;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

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
        // If the authentication is successful, return 200 with a token and the user ID. Otherwise, return an HTTP
        // error 405 so that the frontend can process accordingly. Remember that 401 is a reserved code that will
        // reset the authentication token and will redirect the user to the login page.
        $authenticatedData = $this->authenticationService->authenticate($data);
        if (!empty($authenticatedData)) {
            $this->getResponse()->setStatusCode(200);
            /** @var User $user */
            $user = $authenticatedData['user'];

            if (in_array($authenticatedData['token'], array('2FARequired', '2FAToBeConfigured'))) {
                $this->getResponse()->setStatusCode(401);
            }

            if ($authenticatedData['token'] == '2FAToBeConfigured') {
                $jsonModel = new JsonModel([
                    'token' => $authenticatedData['token'],
                    'uid' => $user->getId(),
                    'language' => $user->getLanguage(),
                    'secret' => $authenticatedData['secret'],
                    'qrcode' => $authenticatedData['qrcode']
                ]);
            } else {
                $jsonModel = new JsonModel([
                    'token' => $authenticatedData['token'],
                    'uid' => $user->getId(),
                    'language' => $user->getLanguage(),
                ]);
            }

            return $jsonModel;
        }

        $this->getResponse()->setStatusCode(401);

        return new JsonModel([]);
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
