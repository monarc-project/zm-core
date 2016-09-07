<?php
namespace MonarcCore\Service;

/**
 * Guide Item Service
 *
 * Class GuideItemService
 * @package MonarcCore\Service
 */
class GuideItemService extends AbstractService
{
    protected $guideTable;

    protected $dependencies = ['guide'];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $guide = (isset($data['guide'])) ? $data['guide'] : null;

        $position = $this->managePositionCreation('guide', $guide, (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;

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

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $guide = (isset($data['guide'])) ? $data['guide'] : null;

        $entity = $this->get('table')->getEntity($id);

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePosition('guide', $entity, $guide, $data['implicitPosition'], $previous);
        }

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

        $entityGuideId = $entity['guide']->id;
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('guide', $entityGuideId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }

}