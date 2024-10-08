<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\InstanceRiskOp;
use Monarc\Core\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Entity\ObjectSuperClass;
use Monarc\Core\Entity\RolfRiskSuperClass;

class InstanceRiskOpTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceRiskOp::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return InstanceRiskOp[]
     */
    public function getInstancesRisksOp(AnrSuperClass $anr, array $instancesIds, array $params = []): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('iro');

        if (empty($instancesIds)) {
            $instancesIds[] = 0;
        }
        $queryBuilder
            ->where($queryBuilder->expr()->in('iro.instance', $instancesIds))
            ->andWhere('iro.anr = :anr')
            ->setParameter('anr', $anr);

        if (isset($params['kindOfMeasure'])) {
            $queryBuilder->andWhere('iro.kindOfMeasure = :kim')
                ->setParameter('kim', $params['kindOfMeasure']);
        }

        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $queryBuilder->andWhere('iro.cacheNetRisk > :cnr')
                ->setParameter('cnr', $params['thresholds']);
        }

        if (!empty($params['order'])) {
            $orderDirection = isset($params['order_direction'])
                && strtoupper(trim($params['order_direction'])) !== Criteria::ASC ? Criteria::DESC : Criteria::ASC;
            $queryBuilder->orderBy('iro.' . $params['order'], $orderDirection);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return InstanceRiskOpSuperClass[]
     */
    public function findByRolfRisk(RolfRiskSuperClass $rolfRisk): array
    {
        return $this->getRepository()->createQueryBuilder('oprisk')
            ->where('oprisk.rolfRisk = :rolfRisk')
            ->setParameter('rolfRisk', $rolfRisk)
            ->getQuery()
            ->getResult();
    }

    /**
     * The method is overridden on Client side with the anr relation in the where clause.
     *
     * @return InstanceRiskOp[]
     */
    public function findByObjectAndRolfRisk(ObjectSuperClass $object, RolfRiskSuperClass $rolfRisk): array
    {
        return $this->getRepository()->createQueryBuilder('oprisk')
            ->innerJoin('oprisk.object', 'o')
            ->where('o.uuid = :objectUuid')
            ->andWhere('oprisk.rolfRisk = :rolfRisk')
            ->setParameter('objectUuid', $object->getUuid())
            ->setParameter('rolfRisk', $rolfRisk)
            ->getQuery()
            ->getResult();
    }
}
