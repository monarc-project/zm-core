<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Model;
use MonarcCore\Model\Table\ModelTable;

class ModelService extends AbstractService
{
    protected $modelTable;

    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {

        $modelTable = $this->get('modelTable');

        return $modelTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('label1', 'label2', 'label3', 'label4')));
    }

    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $filter = $this->parseFrontendFilter($filter, []);
        $filter['isDeleted'] = 0;

        /** @var ModelTable $modelTable */
        $modelTable = $this->get('modelTable');

        return $modelTable->fetchAllFiltered(
            array(
                'id', 'status',
                'label1', 'label2', 'label3', 'label4',
                'description1', 'description2', 'description3', 'description4',
                'isScalesUpdatable', 'isDefault', 'isDeleted', 'isGeneric', 'isRegulator',
                'showRolfBrut'
            ),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $filter
        );
    }

    public function getEntity($id)
    {
        return $this->get('modelTable')->get($id);
    }


    public function create($data) {

        $modelEntity = new Model();

        $modelEntity->exchangeArray($data);

        $modelTable = $this->get('modelTable');
        $modelTable->save($modelEntity);
    }

    public function update($id, $data) {
        $modelTable = $this->get('modelTable');

        $entity = $modelTable->getEntity($id);

        $data['id'] = $id;

        if ($entity != null) {

            $entity->exchangeArray($data);
            $modelTable->save($entity);
            return true;
        } else {
            return false;
        }
    }

    public function delete($id) {
        $modelTable = $this->get('modelTable');

        $entity = $modelTable->getEntity($id);

        if ($entity) {

            $data = $entity->toArray()[0];
            $data['isDeleted'] = 1;

            foreach($data as $key => $value) {
                if ($value == true) {
                    $data[$key] = 1;
                } else if ($value == false) {
                    $data[$key] = 0;
                }
            }

            if ($entity != null) {

                $entity->exchangeArray($data);
                $modelTable->save($entity);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}