<?php
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
	public function get($id){
        $anrId = (int) $this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            header('Content-Type: text/csv');
            die($this->getService()->getCsvRisks($anrId, ['id' => $id], $params));
        } else {
            $risks = $this->getService()->getRisks($anrId, ['id' => $id], $params);
            return new JsonModel([
                'count' => count($risks),
                'risks' => array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
            ]);
        }
	}

	public function getList(){
        $anrId = (int) $this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            header('Content-Type: text/csv');
            die($this->getService()->getCsvRisks($anrId, null, $params));
        } else {
            $risks = $this->getService()->getRisks($anrId, null, $params);
            return new JsonModel([
                'count' => count($risks),
                'risks' => array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
            ]);
        }
	}
	public function create($data){
        $this->methodNotAllowed();
	}
	public function delete($id){
		$this->methodNotAllowed($id);
	}
	public function deleteList($data){
		$this->methodNotAllowed();
	}
	public function update($id, $data){
		$this->methodNotAllowed();
	}
	public function patch($id, $data){
		$this->methodNotAllowed();
	}

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