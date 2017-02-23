<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use MonarcCore\Service\ModelService;
use Zend\View\Model\JsonModel;

/**
 * Api Models Controller
 *
 * Class ApiModelsController
 * @package MonarcCore\Controller
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
        $status = strval($this->params()->fromQuery('status',\MonarcCore\Model\Entity\AbstractEntity::STATUS_ACTIVE));

        $service = $this->getService();
        switch($status){
            case strval(\MonarcCore\Model\Entity\AbstractEntity::STATUS_INACTIVE):
                $filterAnd = ['status' => \MonarcCore\Model\Entity\AbstractEntity::STATUS_INACTIVE];
                break;
            default:
            case strval(\MonarcCore\Model\Entity\AbstractEntity::STATUS_ACTIVE):
                $filterAnd = ['status' => \MonarcCore\Model\Entity\AbstractEntity::STATUS_ACTIVE];
                break;
            case 'all':
                $filterAnd = ['status' => ['op'=> 'IN', 'value' => [\MonarcCore\Model\Entity\AbstractEntity::STATUS_INACTIVE,\MonarcCore\Model\Entity\AbstractEntity::STATUS_ACTIVE]]];
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
            'count' => $service->getFilteredCount($page, $limit, $order, $filter,$filterAnd),
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