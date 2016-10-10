<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Table\InstanceTable;

/**
 * Scale Type Service
 *
 * Class ScaleImpactTypeService
 * @package MonarcCore\Service
 */
class ScaleImpactTypeService extends AbstractService
{
    protected $anrTable;
    protected $scaleTable;
    protected $instanceTable;
    protected $instanceConsequenceService;

    protected $dependencies = ['anr', 'scale'];
    protected $forbiddenFields = ['anr', 'scale'];
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
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true) {

        $anrId = $data['anr'];

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $parent = (isset($data['scale'])) ? $data['scale'] : null;
        $position = $this->managePositionCreation('scale', $parent, (int) $data['implicitPosition'], $previous);

        $data['position'] = $position;
        if (!isset($data['isSys'])) {
            $data['isSys'] = 0;
        }
        if (!isset($data['isHidden'])) {
            $data['isSys'] = 0;
        }
        if (!isset($data['type'])) {
            $data['type'] = 9;
        }

        //$entity = $this->get('entity');
        $class = $this->get('entity');
        $entity = new $class();

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $id = $this->get('table')->save($entity);

        //retrieve all instances for current anr
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['anr' => $anrId]);

        foreach ($instances as $instance) {

            //create instances consequences
            $dataConsequences = [
                'anr' => $anrId,
                'instance' => $instance->id,
                'object' => $instance->object->id,
                'scaleImpactType' => $id,
            ];
            /** @var InstanceConsequenceService $instanceConsequenceService */
            $instanceConsequenceService = $this->get('instanceConsequenceService');
            $instanceConsequenceService->create($dataConsequences);
        }

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $parent = (isset($data['scale'])) ? $data['scale'] : null;
        $position = $this->managePositionCreation('scale', $parent, (int) $data['implicitPosition'], $previous);

        $data['position'] = $position;
        $data['isSys'] = 0;
        $data['type'] = 9;

        $entity = $this->get('table')->getEntity($id);

        //security
        $this->filterPostFields($data, $entity);

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

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }
}