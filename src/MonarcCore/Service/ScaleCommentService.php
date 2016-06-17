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
    protected $scaleTypeImpactTable;
    protected $dependencies = ['scale', 'scaleTypeImpact'];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        if (array_key_exists('scale', $data)) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $entity->setScale($scale);
            if (($scale->type !=1) && (array_key_exists('scaleTypeImpact', $data))) {
                unset($data['scaleTypeImpact']);
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
        if (array_key_exists('scale', $data)) {
            $scale = $this->get('scaleTable')->getEntity($data['scale']);
            $entity->setScale($scale);
            if (($scale->type !=1) && (array_key_exists('scaleTypeImpact', $data))) {
                unset($data['scaleTypeImpact']);
            }
        }
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }
}