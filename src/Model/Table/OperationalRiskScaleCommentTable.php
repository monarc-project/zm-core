<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;

class OperationalRiskScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalRiskScaleComment::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return OperationalRiskScaleComment[]
     */
    public function findAllByAnrAndIndexAndScaleType(AnrSuperClass $anr, int $scaleIndex, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->innerJoin('t.operationalRiskScale', 'ors')
            ->where('t.anr = :anr')
            ->andWhere('t.scaleIndex = :scaleIndex')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setParameter('scaleIndex', $scaleIndex)
            ->getQuery()
            ->getResult();
    }

    /**
     * If we modify the value which the index correspond to the max of the scale, we have to find the comment with
     * higher index to update them to avoid error.
     *
     * @return OperationalRiskScaleComment[]
     */
    public function findNextCommentsToUpdateByAnrAndIndexAndType(AnrSuperClass $anr, int $scaleIndex, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->innerJoin('t.operationalRiskScale', 'ors')
            ->where('t.anr = :anr')
            ->andWhere('t.scaleIndex > :scaleIndex')
            ->andWhere('ors.type = :type')
            ->andWhere('ors.max = :scaleIndex')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setParameter('scaleIndex', $scaleIndex)
            ->getQuery()
            ->getResult();
    }
}
