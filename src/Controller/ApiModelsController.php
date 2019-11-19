<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Service\ModelService;
use Zend\View\Model\JsonModel;

/**
 * Api Models Controller
 *
 * Class ApiModelsController
 * @package Monarc\Core\Controller
 */
class ApiModelsController extends AbstractController
{
    protected $dependencies = ['anr'];
    protected $name = 'models';

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $isGeneric = $this->params()->fromQuery('isGeneric');
        $status = strval($this->params()->fromQuery('status',\Monarc\Core\Model\Entity\AbstractEntity::STATUS_ACTIVE));

        $service = $this->getService();
        switch($status){
            case strval(\Monarc\Core\Model\Entity\AbstractEntity::STATUS_INACTIVE):
                $filterAnd = ['status' => \Monarc\Core\Model\Entity\AbstractEntity::STATUS_INACTIVE];
                break;
            default:
            case strval(\Monarc\Core\Model\Entity\AbstractEntity::STATUS_ACTIVE):
                $filterAnd = ['status' => \Monarc\Core\Model\Entity\AbstractEntity::STATUS_ACTIVE];
                break;
            case 'all':
                $filterAnd = ['status' => ['op'=> 'IN', 'value' => [\Monarc\Core\Model\Entity\AbstractEntity::STATUS_INACTIVE,\Monarc\Core\Model\Entity\AbstractEntity::STATUS_ACTIVE]]];
                break;
        }

        if (!is_null($isGeneric)) {
            $filterAnd['isGeneric'] = $isGeneric;
        }
        $entities = $service->getList($page, $limit, $order, $filter,$filterAnd);

        if (count($this->dependencies)) {
            foreach ($entities as $key => $entity) {
                $this->formatDependencies($entities[$key], $this->dependencies);
            }
        }

        return new JsonModel(array(
            'count' => $service->getFilteredCount($filter,$filterAnd),
            $this->name => $entities
        ));
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        /** @var ModelService $modelService */
        $modelService = $this->getService();
        $entity = $modelService->getModelWithAnr($id);

        return new JsonModel($entity);
    }
}
