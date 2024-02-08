<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\SoaScaleCommentSuperClass;

class SoaScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = SoaScaleComment::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return SoaScaleCommentSuperClass[]
     */
    public function findByAnrOrderByIndex(AnrSuperClass $anr, bool $onlyVisible = false): array
    {
        $queryBuilder =  $this->getRepository()->createQueryBuilder('ssc')
            ->where('ssc.anr = :anr')
            ->setParameter('anr', $anr)
            ->orderBy('ssc.scaleIndex', Criteria::ASC);
        if ($onlyVisible) {
            $queryBuilder->andWhere('ssc.isHidden = 0');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return SoaScaleCommentSuperClass[]
     */
    public function findByAnrIndexedByScaleIndex(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('ssc', 'ssc.scaleIndex')
            ->where('ssc.anr = :anr')
            ->setParameter('anr', $anr)
            ->orderBy('ssc.scaleIndex', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
