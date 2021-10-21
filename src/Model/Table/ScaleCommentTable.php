<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ScaleComment;
use Monarc\Core\Model\Entity\ScaleCommentSuperClass;
use Monarc\Core\Model\Entity\ScaleImpactTypeSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ScaleCommentTable
 * @package Monarc\Core\Model\Table
 */
class ScaleCommentTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, ScaleComment::class, $connectedUserService);
    }

    /**
     * Get By Scale
     *
     * @param $scaleId
     *
     * @return mixed
     */
    public function getByScale($scaleId)
    {
        return $this->getRepository()->createQueryBuilder('s')
            ->select([
                's.scaleValue',
                'IDENTITY(s.scaleImpactType) as scaleImpactType',
                's.comment1',
                's.comment2',
                's.comment3',
                's.comment4',
            ])
            ->where('s.scale = :scaleId')
            ->setParameter(':scaleId', $scaleId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get By Scale And Out Of Range
     *
     * @param $scaleId
     * @param $min
     * @param $max
     *
     * @return array
     */
    public function getByScaleAndOutOfRange($scaleId, $min, $max)
    {
        return $this->getRepository()->createQueryBuilder('s')
            ->select([
                's.id',
                's.scaleValue',
                'IDENTITY(s.scaleImpactType) as scaleImpactType',
                's.comment1',
                's.comment2',
                's.comment3',
                's.comment4',
            ])
            ->where('s.scale = :scaleId AND (s.scaleValue > :max OR s.scaleValue < :min)')
            ->setParameter(':scaleId', $scaleId)
            ->setParameter(':min', $min)
            ->setParameter(':max', $max)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ScaleCommentSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('sc')
            ->where('sc.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ScaleCommentSuperClass[]
     */
    public function findByAnrAndScaleImpactType(AnrSuperClass $anr, ScaleImpactTypeSuperClass $scaleImpactType): array
    {
        return $this->getRepository()->createQueryBuilder('sc')
            ->where('sc.anr = :anr')
            ->andWhere('sc.scaleImpactType = :scaleImpactType')
            ->setParameter('anr', $anr)
            ->setParameter('scaleImpactType', $scaleImpactType)
            ->getQuery()
            ->getResult();
    }

    public function saveEntity(ScaleCommentSuperClass $scaleComment, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($scaleComment);
        if ($flushAll) {
            $em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(ScaleCommentSuperClass $scaleComment, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($scaleComment);
        if ($flush) {
            $em->flush();
        }
    }
}
