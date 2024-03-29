<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskOwner;
use Monarc\Core\Model\Entity\InstanceRiskOwnerSuperClass;

class InstanceRiskOwnerTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceRiskOwner::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByAnrAndName(AnrSuperClass $anr, string $name): ?InstanceRiskOwnerSuperClass
    {
        return $this->getRepository()->createQueryBuilder('iro')
            ->where('iro.anr = :anr')
            ->andWhere('iro.name = :name')
            ->setParameter('anr', $anr)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return InstanceRiskOwnerSuperClass[]
     */
    public function findByAnrAndFilterParams(AnrSuperClass $anr, array $params): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('iro')
            ->where('iro.anr = :anr')
            ->orderBy('iro.name', Criteria::ASC)
            ->setParameter('anr', $anr);

        if (!empty($params['name'])) {
            $queryBuilder->andWhere('iro.name LIKE :name')->setParameter('name', '%' . $params['name'] . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return InstanceRiskOwnerSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('iro')
            ->where('iro.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
