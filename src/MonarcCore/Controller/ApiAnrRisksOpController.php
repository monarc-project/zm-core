<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

/**
 * Api Anr Risks Op Controller
 *
 * Class ApiAnrRisksController
 * @package MonarcCore\Controller
 */
class ApiAnrRisksOpController extends AbstractController
{
    /**
     * @inheritdoc
     */
	public function get($id){
        $anrId = (int) $this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            header('Content-Type: text/csv');
            die($this->getService()->getCsvRisksOp($anrId, ['id' => $id], $params));
        } else {
            $risks = $this->getService()->getRisksOp($anrId, ['id' => $id], $params);
            return new JsonModel([
                'count' => count($risks),
                'oprisks' => array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
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
            die($this->getService()->getCsvRisksOp($anrId, null, $params));
        } else {
            $risks = $this->getService()->getRisksOp($anrId, null, $params);
            return new JsonModel([
                'count' => count($risks),
                'oprisks' => array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
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