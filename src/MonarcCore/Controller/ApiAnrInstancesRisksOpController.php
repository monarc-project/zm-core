<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Instances Risks Op Controller
 *
 * Class ApiAnrInstancesRisksOpController
 * @package MonarcCore\Controller
 */
class ApiAnrInstancesRisksOpController extends AbstractController
{
    protected $name = 'instances-oprisks';

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
     * Update
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function update($id, $data){
        $risk = $this->getService()->update($id, $data);
        unset($risk['anr']);
        unset($risk['instance']);
        unset($risk['object']);
        unset($risk['rolfRisk']);

        return new JsonModel($risk);
    }
}