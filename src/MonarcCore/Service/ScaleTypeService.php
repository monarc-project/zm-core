<?php
namespace MonarcCore\Service;

/**
 * Scale Type Service
 *
 * Class ScaleTypeService
 * @package MonarcCore\Service
 */
class ScaleTypeService extends AbstractService
{
    protected $anrTable;
    protected $scaleTable;
    protected $dependencies = ['anr', 'scale'];
    protected $types = [
        1 => 'C',
        2 => 'I',
        3 => 'D',
        4 => 'R',
        5 => 'O',
        6 => 'L',
        7 => 'F',
        8 => 'P',
        9 => 'CUS',
    ];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

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

        $scales = parent::getList($page, $limit, $order, $filter, $filterAnd);

        $types = $this->getTypes();

        foreach ($scales as $key => $scale) {
            $scales[$key]['type'] = $types[$scale['type']];
        }

        return $scales;
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $parent = (array_key_exists('scale', $data)) ? $data['scale'] : null;
        $position = $this->managePositionCreation('scale', $parent, (int) $data['implicitPosition'], $previous);

        $data['position'] = $position;
        if (!array_key_exists('isSys', $data)) {
            $data['isSys'] = 0;
        }
        if (!array_key_exists('type', $data)) {
            $data['type'] = 9;
        }

        //$entity = $this->get('entity');
        $class = $this->get('entity');
        $entity = new $class();

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
     */
    public function update($id,$data){

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $parent = (array_key_exists('scale', $data)) ? $data['scale'] : null;
        $position = $this->managePositionCreation('scale', $parent, (int) $data['implicitPosition'], $previous);

        $data['position'] = $position;
        $data['isSys'] = 0;
        $data['type'] = 9;

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
     * @throws \Exception
     */
    public function delete($id) {

        $entity = $this->getEntity($id);

        if ($entity['isSys']) {
            throw new \Exception('Not Authorized', '401');
        }

        $parentId = $entity['scale']->id;
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('scale', $parentId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }
}