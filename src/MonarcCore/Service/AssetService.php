<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Table\AssetTable;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{
    protected $assetTable;

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return bool|mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {

        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');

        return $assetTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('label1', 'label2', 'label3', 'label4')));
    }

    /**
     * get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array|bool
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');

        return $assetTable->fetchAllFiltered(
            array(
                'id', 'status',
                'label1', 'label2', 'label3', 'label4',
                'description1', 'description2', 'description3', 'description4',
                'mode', 'type', 'code'
            ),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, [])
        );
    }

    /**
     * Get Entity
     * @param $id
     * @return mixed
     */
    public function getEntity($id)
    {
        return $this->get('assetTable')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $assetEntity = new Asset();
        $assetEntity->exchangeArray($data);


        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');
        $assetTable->save($assetEntity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public function update($id, $data) {

        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');

        $entity = $assetTable->getEntity($id);

        $data['id'] = $id;

        if ($entity != null) {

            $entity->exchangeArray($data);
            $assetTable->save($entity);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');

        $assetTable->delete($id);
    }
}