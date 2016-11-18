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

    protected $dependencies = ['question'];

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

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $question = (isset($data['question'])) ? $data['question'] : null;

        $position = $this->managePositionCreation('question', $question, (int) $data['implicitPosition'], $previous);
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
        $question = (isset($data['question'])) ? $data['question'] : null;

        $entity = $this->get('table')->getEntity($id);

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePosition('question', $entity, $question, $data['implicitPosition'], $previous);
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
    public function delete($id) {

        $entity = $this->getEntity($id);

        $entityQuestionId = $entity['question']->id;
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('question', $entityQuestionId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }

}