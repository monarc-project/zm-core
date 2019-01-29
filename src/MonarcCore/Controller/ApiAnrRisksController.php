<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Risks Controller
 *
 * Class ApiAnrRisksController
 * @package MonarcCore\Controller
 */
class ApiAnrRisksController extends AbstractController
{
    protected $name = 'risks';

    /**
     * @inheritdoc
     */
	public function get($id){
        $anrId = (int) $this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            header('Content-Type: text/csv');
            die($this->getService()->getCsvRisks($anrId, ['id' => $id], $params));
        } else {
            $lst = $this->getService()->getRisks($anrId, ['id' => $id], $params);
            return new JsonModel([
                'count' => count($lst),
                $this->name => $params['limit'] > 0 ? array_slice($lst, ($params['page'] - 1) * $params['limit'], $params['limit']) : $lst,
            ]);
        }
	}

    /**
     * @inheritdoc
     */
	public function getList(){
        $anrId = (int) $this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            header('Content-Type: text/csv');
            die($this->getService()->getCsvRisks($anrId, null, $params));
        } else {
            $lst = $this->getService()->getRisks($anrId, null, $params);
            return new JsonModel([
                'count' => count($lst),
                $this->name => $params['limit'] > 0 ? array_slice($lst, ($params['page'] - 1) * $params['limit'], $params['limit']) : $lst,
            ]);
        }
	}

    /**
     * @inheritdoc
     */
	public function create($data){
        $this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function delete($id){
		$this->methodNotAllowed($id);
	}

    /**
     * @inheritdoc
     */
	public function deleteList($data){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function update($id, $data){
		$this->methodNotAllowed();
	}

    /**
     * @inheritdoc
     */
	public function patch($id, $data){
		$this->methodNotAllowed();
	}

    /**
     * Helper method to parse filter params from the frontend
     * @return array Parsed params
     */
	protected function parseParams() {
        $keywords = $this->params()->fromQuery("keywords");
        $kindOfMeasure = $this->params()->fromQuery("kindOfMeasure");
        $order = $this->params()->fromQuery("order", "maxRisk");
        $order_direction = $this->params()->fromQuery("order_direction", "desc");
        $thresholds = $this->params()->fromQuery("thresholds");
        $page = $this->params()->fromQuery("page", 1);
        $limit = $this->params()->fromQuery("limit", 50);

        return [
            'keywords' => $keywords,
            'kindOfMeasure' => $kindOfMeasure,
            'order' => $order,
            'order_direction' => $order_direction,
            'thresholds' => $thresholds,
            'page' => $page,
            'limit' => $limit
        ];
    }
}