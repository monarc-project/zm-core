<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

/**
 * Class QuestionTable
 * @package Monarc\Core\Model\Table
 */
class QuestionTable extends AbstractEntityTable
{
  /**
  * Return the last position of question
  * @return int;
  */
  public function maxQuestionPosition()
  {
    $maxPosition = $this->getRepository()->createQueryBuilder('t')
            ->select(array('max(t.position)'));
    $maxPosition = $maxPosition->getQuery()
            ->getResult();
    return $maxPosition[0][1];
  }

  /**
  * Return the min position of question
  * @return int
  */
  public function minQuestionPosition()
  {
    $minPosition = $this->getRepository()->createQueryBuilder('t')
            ->select(array('min(t.position)'));
    $minPosition = $minPosition->getQuery()
            ->getResult();
    return $minPosition[0][1];
  }

  /**
  * Return the previous id of a given position
  * @param $position (int) the position of the element which we need to know the previous one
  * @return int
  */
  public function getPrevious($position)
  {
    $previous = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.position <'. $position)
            ->orderBy('t.position', 'DESC')
            ->setMaxResults(1);
    $previous = $previous->getQuery()
            ->getResult();
    return $previous[0]['id'];
  }

  /**
  * Increment all the position by one begining at the $begin parameter
  * @param $begin (int)
  */
  public function movePosition($begin = 0)
  {
    $q = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . '+1')
            ->where('t.position' . '>' . ' :position')
            ->setParameter(':position', $begin)
            ->getQuery()
            ->getResult();
    return $q;
  }
}
