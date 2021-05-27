<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\ScaleComment;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ScaleCommentOpTable
 * @package Monarc\Core\Model\Table
 */
class ScaleCommentOpTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, ScaleComment::class, $connectedUserService);
    }
    /**
     * Get By Scale
     *
     * @param $scaleId
     * @return mixed
     * @throws \Exception
     */
    public function getByScale($scaleId)
    {
        $comments = $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.val', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId')
            ->setParameter(':scaleId', $scaleId)
            ->getQuery()
            ->getResult();

        return $comments;
    }

    /**
     * Get By Scale And Out Of Range
     *
     * @param $scaleId
     * @param $min
     * @param $max
     * @return array
     */
    public function getByScaleAndOutOfRange($scaleId, $min, $max)
    {
        $comments = $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.id', 's.val', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId AND (s.val > :max OR s.val < :min)')
            ->setParameter(':scaleId', $scaleId)
            ->setParameter(':min', $min)
            ->setParameter(':max', $max)
            ->getQuery()
            ->getResult();

        return $comments;
    }
}
