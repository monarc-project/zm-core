<?php
namespace MonarcCore\Service;

/**
 * Question Service
 *
 * Class QuestionService
 * @package MonarcCore\Service
 */
class QuestionService extends AbstractService
{
    protected $choiceTable;
    protected $anrTable;
    protected $userAnrTable;

    protected $dependencies = ['anr'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null){

        if(empty($order)){
            $order = 'position';
        }

        $data = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        // Append choices
        foreach ($data as &$d) {
            if ($d['type'] == 2) {
                $d['choices'] = $this->get('choiceTable')->fetchAllFiltered([], 1, 0, null, null, ['question' => $d['id']]);
            }
        }

        return $data;
    }

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

        $entity->setDbAdapter($this->get('table')->getDb());

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
        $entity->setDbAdapter($this->get('table')->getDb());

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
