<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use MonarcCore\Model\Entity\Object;
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
     * Get list
     *
     * @return JsonModel
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
            'count' => $service->getFilteredCount($page, $limit, $order, $filter, $asset, $category, $model, $anr),
            $this->name => $objects
        ));
    }

    /**
     * Get
     *
     * @param mixed $id
     * @return JsonModel
     */
    public function get($id)
    {
        $anr = (int) $this->params()->fromRoute('anrid');

        /** @var ObjectService $service */
        $service = $this->getService();
        $object = $service->getCompleteEntity($id, Object::CONTEXT_ANR, $anr);

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
     * Parents Action
     *
     * @return JsonModel
     */
    public function parentsAction(){
        $matcher = $this->getEvent()->getRouteMatch();
        return new JsonModel($this->getService()->getParents($matcher->getParam('anrid'), $matcher->getParam('id')));
    }
}

