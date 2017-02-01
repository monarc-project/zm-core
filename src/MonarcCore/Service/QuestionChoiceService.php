<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

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
    protected $userAnrTable;
    protected $dependencies = ['anr', 'question'];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
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
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);


        return $this->get('table')->save($entity);
    }

    /**
     * Replace List
     *
     * @param $data
     * @param $anrId
     */
    public function replaceList($data, $anrId)
    {
        /** @var QuestionChoiceTable $table */
        $table = $this->get('table');

        // Remove existing choices
        $questions = $table->fetchAllFiltered(['id'], 1, 0, null, null, ['question' => $data['questionId']]);
        $i = 1;
        $nbQuestions = count($questions);
        foreach ($questions as $q) {
            $table->delete($q['id'], ($i == $nbQuestions));
            $i++;
        }

        /** @var QuestionTable $questionTable */
        $questionTable = $this->get('questionTable');
        $question = $questionTable->getEntity($data['questionId']);

        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);

        // Add new choices
        $pos = 1;
        $i = 1;
        $nbChoices = $data['choice'];
        foreach ($data['choice'] as $c) {
            $c['position'] = $pos;
            unset($c['question']);

            /** @var QuestionChoice $choiceEntity */
            $choiceEntity = new QuestionChoice();
            $choiceEntity->setQuestion($question);
            $choiceEntity->setAnr($anr);
            $choiceEntity->squeezeAutoPositionning(true);
            $choiceEntity->exchangeArray($c);
            $table->save($choiceEntity, ($i == $nbChoices));
            ++$pos;
            $i++;
        }
    }
}