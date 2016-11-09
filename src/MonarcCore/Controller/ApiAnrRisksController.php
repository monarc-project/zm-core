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
        return new JsonModel($this->getService()->getRisks($anrId, $id));
	}

	public function getList(){
        $anrId = (int) $this->params()->fromRoute('anrid');
		return new JsonModel($this->getService()->getRisks($anrId));
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
}