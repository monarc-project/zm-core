<?php
namespace MonarcCore\Service;

/**
 * Scale Comment Service
 *
 * Class ScaleCommentService
 * @package MonarcCore\Service
 */
class ScaleCommentService extends AbstractService
{
    protected $scaleTable;
    protected $scaleService;
    protected $scaleImpactTypeService;
    protected $scaleImpactTypeTable;
    protected $dependencies = ['scale', 'scaleImpactType'];
    protected $forbiddenFields = ['anr', 'scale'];

    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null){
        $comments = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        /*$scaleTypes = $this->get('scaleService')->getTypes();
        $scaleTypesImpact = $this->get('scaleTypeService')->getTypes();

        foreach ($comments as $key => $comment) {
            $comments[$key]['scaleTypeImpact']->type = $scaleTypesImpact[$comment['scaleTypeImpact']->type];
        }*/

        return $comments;
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        if (isset($data['scale'])) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $entity->setScale($scale);
            if ($scale->type !=1) {
                unset($data['scaleImpactType']);
            }
        }
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

        $entity = $this->get('table')->getEntity($id);
        if (isset($data['scale'])) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $entity->setScale($scale);
            if ($scale->type !=1) {
                unset($data['scaleImpactType']);
            }
        }
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
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