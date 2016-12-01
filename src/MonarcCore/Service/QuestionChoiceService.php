<?php
namespace MonarcCore\Service;

/**
 * Question Choice Service
 *
 * Class QuestionChoiceService
 * @package MonarcCore\Service
 */
class QuestionChoiceService extends AbstractService
{
    protected $questionTable;
    protected $anrTable;

    protected $dependencies = ['anr', 'question'];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true) {
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');

        $entity->exchangeArray($data);

        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){
        $entity = $this->get('table')->getEntity($id);
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
    public function delete($id) {
        $entity = $this->getEntity($id);

        $this->get('table')->delete($id);
    }

}