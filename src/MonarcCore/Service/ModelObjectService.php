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
class ModelObjectService extends AbstractService
{
    protected $anrService;
    protected $anrTable;
    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        if(!empty($data['id'])){
            $obj = $this->get('table')->get($id);
            if(!$obj->get('model') && $obj->get('type') == 'bdc'){
                $data = $obj->getJsonArray();
                $data['source'] = $obj->get('id');
                $data['type'] = 'anr';
                unset($data['creator']);
                unset($data['created_at']);
                unset($data['updater']);
                unset($data['updated_at']);
            }
            unset($data['id'])
        }
        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id,$idm,$data){
        $entity = $this->get('table')->getEntity($id);

        if($entity->get('model') != $idm || $entity->get('type') != 'anr'){
            throw new \Exception('Entity `id` not found.');
            return false;
        }

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }


    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id,$idm) {
        $entity = $this->get('table')->getEntity($id);

        if($entity->get('model') != $idm || $entity->get('type') != 'anr'){
            throw new \Exception('Entity `id` not found.');
            return false;
        }
        $this->get('table')->delete($id);
    }
}