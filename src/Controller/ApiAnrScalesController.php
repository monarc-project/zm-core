<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Scales Controller
 *
 * Class ApiAnrScalesController
 * @package Monarc\Core\Controller
 */
class ApiAnrScalesController extends AbstractController
{
    protected $dependencies = ['anr'];
    protected $name = 'scales';

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $anrId = (int) $this->params()->fromRoute('anrId');

        $scales = $this->getService()->getList($page, $limit, $order, $filter, ['anr' => $anrId]);
        foreach($scales as $key => $scale){
            $this->formatDependencies($scales[$key], $this->dependencies);
        }

        return new JsonModel(array(
            'count' => $this->getService()->getFilteredCount($filter, ['anr' => $anrId]),
            $this->name => $scales
        ));
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
    public function delete($id)
    {
        return $this->methodNotAllowed();
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');

        if ($anrId) {
            $data['anr'] = $anrId;
        }
        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }
}

