<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Instances Risks Controller
 *
 * Class ApiAnrInstancesRisksController
 * @package Monarc\Core\Controller
 */
class ApiAnrInstancesRisksController extends AbstractController
{
    protected $dependencies = ['anr','amv', 'asset', 'threat', 'vulnerability', 'instance'];
    protected $name = 'instances-risks';

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

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->patch($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * @inheritdoc
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

