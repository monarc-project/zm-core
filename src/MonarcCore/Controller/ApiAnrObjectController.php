<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Controller;

use MonarcCore\Model\Entity\MonarcObject;
use MonarcCore\Service\ObjectService;
use Zend\View\Model\JsonModel;

/**
 * Api Anr Object Controller
 *
 * Class ApiAnrObjectController
 * @package MonarcCore\Controller
 */
class ApiAnrObjectController extends AbstractController
{
    protected $dependencies = ['category', 'asset', 'rolfTag'];
    protected $name = 'objects';

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $asset = (int) $this->params()->fromQuery('asset');
        $category = (int) $this->params()->fromQuery('category');
        $model = (int) $this->params()->fromQuery('model');
        $lock = $this->params()->fromQuery('lock');
        $anr = (int) $this->params()->fromRoute('anrid');

        /** @var ObjectService $service */
        $service = $this->getService();
        $objects =  $service->getListSpecific($page, $limit, $order, $filter, $asset, $category, $model, $anr, $lock);

        if ($lock == 'true') {
            foreach($objects as $key => $object){
                $this->formatDependencies($objects[$key], $this->dependencies);
            }
        }

        return new JsonModel(array(
            'count' => $service->getFilteredCount($filter, $asset, $category, $model, $anr),
            $this->name => $objects
        ));
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $anr = (int) $this->params()->fromRoute('anrid');

        /** @var ObjectService $service */
        $service = $this->getService();
        $object = $service->getCompleteEntity($id, MonarcObject::CONTEXT_ANR, $anr);

        if (count($this->dependencies)) {
            $this->formatDependencies($object, $this->dependencies);
        }

        $anrs = [];
        foreach($object['anrs'] as $key => $anr) {
            $anrs[] = $anr->getJsonArray();
        }
        $object['anrs'] = $anrs;

        return new JsonModel($object);
    }

    /**
     * @inheritdoc
     */
    public function parentsAction(){
        $matcher = $this->getEvent()->getRouteMatch();
        return new JsonModel($this->getService()->getParents($matcher->getParam('anrid'), $matcher->getParam('id')));
    }
}

