<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Threat;
use MonarcCore\Model\Entity\Model;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package MonarcCore\Service
 */
class ThreatService extends AbstractService
{

    protected $threatTable;
    protected $threatEntity;
    protected $modelTable;

    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];

     /**
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {
        $threatTable = $this->get('threatTable');

        return $threatTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns));
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $threatTable = $this->get('threatTable');

        return $threatTable->fetchAllFiltered(
            array_keys($this->get('threatEntity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns)
        );
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){

        return $this->get('threatTable')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $threatTable = $this->get('threatTable');
        $threatEntity = $this->get('threatEntity');
        $threatEntity->exchangeArray($data);

        $mods = $threatEntity->get('models');
        if (!empty($mods)) {
            $modelTable = $this->get('modelTable');
            foreach ($mods as $k => $modelid) {
                if(!empty($modelid)){
                    $model = $modelTable->getEntity($modelid);
                    $threatEntity->setModel($k,$model);
                }
            }
        }
        return $threatTable->save($threatEntity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $threatEntity = $this->get('threatEntity');
        $threatEntity->delete($id);
    }

    /**
     * Update Entity
     *
     * @param $id
     * @param $data
     */
    public function update($id,$data){
        $threatTable = $this->get('threatTable');
        $mods = isset($data['models'])?$data['models']:array();
        unset($data['models']);
        $threatEntity->exchangeArray($data);
        $threatEntity->get('models')->initialize();
        foreach($threatEntity->get('models') as $k => $v){
            if(in_array($v->get('id'), $mods)){
                unset($mods[array_search($v->get('id'), $mods)]);
            }else{
                $threatEntity->get('models')->removeElement($v);
            }
        }
        if(!empty($mods)){
            $modelTable = $this->get('modelTable');
            foreach ($mods as $k => $modelid) {
                if(!empty($modelid)){
                    $model = $modelTable->getEntity($modelid);
                    $threatEntity->setModel($k,$model);
                }
            }
        }
        return $threatTable->save($threatEntity);
    }
}