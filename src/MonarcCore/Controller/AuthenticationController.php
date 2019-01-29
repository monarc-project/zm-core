<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Authentication Controller
 *
 * Class AuthenticationController
 * @package MonarcCore\Controller
 */
class AuthenticationController extends AbstractController
{
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
        if ($this->getService()->authenticate($data, $t, $uid, $language)) {
            $this->response->setStatusCode(200);
            return new JsonModel(array('token' => $t, 'uid' => $uid, 'language' => $language));
        } else {
            $this->response->setStatusCode(405);
            return new JsonModel(array());
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteList($data)
    {
        $request = $this->getRequest();
        $token = $request->getHeader('token');

        $this->getService()->logout(array('token' => $token->getFieldValue()));
        return new JsonModel(array());
    }
}