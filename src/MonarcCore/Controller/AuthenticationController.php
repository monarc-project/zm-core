<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
     * Create
     *
     * @param mixed $data
     * @return JsonModel
     */
    public function create($data)
    {
        $t = null;
        $uid = null;
        $language = null;

        if ($this->getService()->authenticate($data, $t, $uid, $language)) {
            $this->response->setStatusCode(200);
            return new JsonModel(array('token' => $t, 'uid' => $uid, 'language' => $language));
        } else {
            $this->response->setStatusCode(405);
            return new JsonModel(array());
        }
    }

    /**
     * Delete List
     *
     * @param mixed $data
     * @return JsonModel
     */
    public function deleteList($data)
    {
        $request = $this->getRequest();
        $token = $request->getHeader('token');

        $this->getService()->logout(array('token' => $token->getFieldValue()));
        return new JsonModel(array());
    }
}