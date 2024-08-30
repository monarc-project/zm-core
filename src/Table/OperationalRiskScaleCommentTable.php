<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\OperationalRiskScaleComment;

class OperationalRiskScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalRiskScaleComment::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return OperationalRiskScaleComment[]
     */
    public function findByAnrAndScaleTypeOrderByIndex(AnrSuperClass $anr, int $scaleType): array
    {
        return $this->getRepository()->createQueryBuilder('orsc')
            ->innerJoin('orsc.operationalRiskScale', 'ors')
            ->where('orsc.anr = :anr')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $scaleType)
            ->orderBy('ors.type', Criteria::ASC)
            ->addOrderBy('orsc.scaleIndex', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
