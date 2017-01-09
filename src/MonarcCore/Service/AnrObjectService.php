<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\ObjectTable;
use MonarcCore\Model\Table\ScaleCommentTable;

/**
 * Anr Object Service
 *
 * Class AnrObjectService
 * @package MonarcCore\Service
 */
class AnrObjectService extends AbstractService{
	protected $objectObjectTable;
	protected $objectService;
	protected $userAnrTable;

	public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $model = null, $anr = null, $lock = null){
		return $this->get('objectService')->getListSpecific($page, $limit, $order, $filter, $asset, $category, $model, $anr, $lock);
	}

	public function getParents($anrid, $id){
		$object = $this->get('table')->getEntity($id);
		if (!$object) {
      throw new \Exception('Entity does not exist', 412);
    }

		//on doit vérifier que l'objet auquel on tente d'accéder est bien rattaché à anrid
		if( ! $this->get('table')->checkInAnr($anrid, $id) ){
			throw new \Exception('Entity does not exist for this ANR', 412);
		}

		return $this->get('objectObjectTable')->getDirectParentsInAnr($anrid, $id);
	}

    /**
     * @param $id
     * @param string $context
     * @param integer $anr
     * @return mixed
     */
    public function getCompleteEntity($id, $context = Object::CONTEXT_BDC, $anr = null) {
        return $this->get('objectService')->getCompleteEntity($id, $context, $anr);
    }
}
