<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Question Service
 *
 * Class QuestionService
 * @package Monarc\Core\Service
 */
class QuestionService extends AbstractService
{
    protected $choiceTable;
    protected $anrTable;
    protected $userAnrTable;
    protected $dependencies = ['anr'];

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        if (empty($order)) {
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

                if (!empty($filterAnd['anr']) && !$d['multichoice']) {
                    $c = $this->get('choiceTable')->getClass();
                    $empty = new $c();
                    array_unshift($d['choices'], $empty->getJsonArray());
                }
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');
        $entity->setDbAdapter($this->get('table')->getDb());
        $table = $this->get('table');
        if (!empty($data['anr'])) {
            $data['mode'] = 1;
            $data['implicitPosition'] = \Monarc\Core\Model\Entity\AbstractEntity::IMP_POS_END;
            $data['type'] = 1; // on force en textarea uniquement
            $data['multichoice'] = 0;
            unset($data['position']);
        }
        //bo case manage position
        // quick fix : TO DO : improve the position management
        if (!empty($data['implicitPosition']) && empty($data['anr']))
        {
          if($data['implicitPosition']==1)//the first
          {
            if($data['position'] != $table->minQuestionPosition())
            {
              $table->movePosition(0);
              $data['position']= 1;
            }
          }
          else if ($data['implicitPosition']==2)// the last
          {
            if($data['position'] != $table->maxQuestionPosition())
              $data['position']= $table->maxQuestionPosition()+1;
          }
          else { //in the middle
              if($data['previous'])
              {
                $previous = $this->get('table')->getEntity($data['previous']);
                $table->movePosition($previous->position);
                $data['position'] = $previous->position+1;
              }
          }
        }

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
        $entity->setDbAdapter($this->get('table')->getDb());
        $table = $this->get('table');
        //FO case
        if (!empty($data['anr'])) {
            if ($data['anr'] == $entity->get('anr')->get('id')) {
                if ($entity->get('mode')) {
                    unset(
                        $data['type'],
                        $data['position'],
                        $data['multichoice']
                    );
                } else {
                    // on ne met pas à jour la question
                    unset(
                        $data['label1'],
                        $data['label2'],
                        $data['label3'],
                        $data['label4']
                    );
                }
                unset($data['mode']);
            } else {
                throw new \Monarc\Core\Exception\Exception('Anr ids diffence', 412);
            }
        }
        //bo case manage position
        // quick fix : TO DO : improve the position management
        if (!empty($data['implicitPosition']) && empty($data['anr']))
        {
          if($data['implicitPosition']==1)//the first
          {
            if($data['position'] != $table->minQuestionPosition())
            {
              $table->movePosition(0);
              $data['position']= 1;
            }
          }
          else if ($data['implicitPosition']==2)// the last
          {
            if($data['position'] != $table->maxQuestionPosition())
              $data['position']= $table->maxQuestionPosition()+1;
          }
          else { //in the middle
              if($data['previous'] != $table->getPrevious($data['position']))
              {
                $previous = $this->get('table')->getEntity($data['previous']);
                $table->movePosition($previous->position);
                $data['position'] = $previous->position+1;

              }
          }
        }

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $entity = $this->getEntity($id);

        if (!empty($entity['anr']) && isset($entity['mode']) && !$entity['mode']) {
            throw new \Monarc\Core\Exception\Exception('Delete question is not possible', 412);
        }

        $this->get('table')->delete($id);
    }
}
