<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Instances Risks Controller
 *
 * Class ApiAnrInstancesRisksController
 * @package MonarcCore\Controller
 */
class ApiAnrInstancesRisksController extends AbstractController
{
    protected $dependencies = ['anr','amv', 'asset', 'threat', 'vulnerability', 'instance'];
    protected $name = 'instances-risks';

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

        $this->getService()->patch($id, $data);

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

        $id = $this->getService()->update($id, $data);

        $entity = $this->getService()->getEntity($id);

        if (count($this->dependencies)) {
            foreach($this->dependencies as $d){
                unset($entity[$d]);
            }
        }

        return new JsonModel($entity);
    }
}

