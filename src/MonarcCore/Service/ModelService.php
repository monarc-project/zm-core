<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $modelTable;
    protected $modelEntity;

    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {
        $modelTable = $this->get('modelTable');

        return $modelTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
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

        $modelTable = $this->get('modelTable');

        return $modelTable->fetchAllFiltered(
            array_keys($this->get('modelEntity')->getJsonArray()),
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

        return $this->get('modelTable')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $modelTable = $this->get('modelTable');
        $modelEntity = $this->get('modelEntity');
        $modelEntity->exchangeArray($data);

        return $modelTable->save($modelEntity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $modelEntity = $this->get('modelEntity');
        $modelEntity->delete($id);
    }

    public function update($id,$data){
        //$modelEntity = $this->get('modelTable')->get($id);
    }
}