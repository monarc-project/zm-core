<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Question Choice Service
 *
 * Class QuestionChoiceService
 * @package Monarc\Core\Service
 */
class QuestionChoiceService extends AbstractService
{
    protected $questionTable;
    protected $anrTable;
    protected $userAnrTable;
    protected $dependencies = ['anr', 'question'];

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
