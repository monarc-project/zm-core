<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->patchConsequence($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * @inheritdoc
     */
    public function getList()
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function create($data)
    {
        return $this->methodNotAllowed();
    }

}

