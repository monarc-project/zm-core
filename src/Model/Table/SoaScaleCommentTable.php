<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Criteria;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Model\Entity\AnrSuperClass;

/**
 * Class SoaScaleTable
 * @package Monarc\Core\Model\Table
 */
class SoaScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = SoaScaleComment::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return SoaScaleComment[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('ssc')
            ->where('ssc.anr = :anr')
            ->setParameter('anr', $anr)
            ->orderBy('ssc.scaleIndex', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SoaScaleComment[]
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
