<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Model;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{

    protected $assetTable;
    protected $assetEntity;
    protected $modelTable;

    protected $repository;

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
        $assetTable = $this->get('assetTable');

        return $assetTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
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

        $assetTable = $this->get('assetTable');

        return $assetTable->fetchAllFiltered(
            array_keys($this->get('assetEntity')->getJsonArray()),
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

        return $this->get('assetTable')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $assetTable = $this->get('assetTable');
        $assetEntity = $this->get('assetEntity');
        $assetEntity->exchangeArray($data);

        $mods = $assetEntity->get('models');
        if (!empty($mods)) {
            $modelTable = $this->get('modelTable');
            foreach ($mods as $k => $modelid) {
                $model = $modelTable->getEntity($modelid);
                $assetEntity->setModel($k,$model);
            }
        }
        return $assetTable->save($assetEntity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $assetEntity = $this->get('assetEntity');
        $assetEntity->delete($id);
    }

    public function update($id,$data){
        $assetTable = $this->get('assetTable');
        $assetEntity = $assetTable->get($id);
        $assetEntity->exchangeArray($data);
        return $assetTable->save($assetEntity);
    }
}