<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Instances Consequences Controller
 *
 * Class ApiAnrInstancesConsequencesController
 * @package MonarcCore\Controller
 */
class ApiAnrInstancesConsequencesController extends AbstractController
{
    protected $name = 'instances-consequences';

    public function getList()
    {
        return $this->methodNotAllowed();
    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    public function create($data)
    {
        return $this->methodNotAllowed();
    }

    /**
     * Patch
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function patch($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->patchConsequence($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Update
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function update($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }
}

