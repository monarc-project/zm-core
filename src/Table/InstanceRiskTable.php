<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRisk;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ThreatSuperClass;

class InstanceRiskTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceRisk::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function findByThreat(ThreatSuperClass $threat): array
    {
        return $this->getRepository()->createQueryBuilder('ir')
            ->where('ir.threat = :threat')
            ->setParameter('threat', $threat)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function findInstancesRisksByParams(
        AnrSuperClass $anr,
        int $languageIndex,
        array $params = []
    ): array {
        $queryBuilder = $this->getRepository()->createQueryBuilder('ir')
            ->innerJoin('ir.instance', 'i')
            ->innerJoin('i.object', 'o')
            ->innerJoin('ir.threat', 't')
            ->innerJoin('ir.vulnerability', 'v')
            ->innerJoin('ir.asset', 'a')
            ->leftJoin('ir.amv', 'amv')
            ->where('ir.anr = :anr')
            ->andWhere('ir.cacheMaxRisk >= -1')
            ->setParameter('anr', $anr);

        if (!empty($params['instanceIds'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('i.id', array_map('\intval', $params['instanceIds'])));
        }

        if (!empty($params['amvs'])) {
            $amvIds = $params['amvs'];
            if (\is_string($amvIds)) {
                $amvIds = explode(',', trim($amvIds));
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('amv.uuid', $amvIds));
        }

        if (isset(
            $params['kindOfMeasure'],
            InstanceRiskSuperClass::getAvailableMeasureTypes()[(int)$params['kindOfMeasure']]
        )) {
            $kindOfMeasure = (int)$params['kindOfMeasure'];
            if ($kindOfMeasure === InstanceRiskSuperClass::KIND_NOT_TREATED) {
                $queryBuilder->andWhere('ir.kindOfMeasure IS NULL OR ir.kindOfMeasure = :kindOfMeasure');
            } else {
                $queryBuilder->andWhere('ir.kindOfMeasure = :kindOfMeasure');
            }
            $queryBuilder->setParameter('kindOfMeasure', $kindOfMeasure);
        }

        if (!empty($params['keywords'])) {
            $queryBuilder->andWhere(
                'a.label' . $languageIndex . ' LIKE :keywords OR ' .
                't.label' . $languageIndex . ' LIKE :keywords OR ' .
                'v.label' . $languageIndex . ' LIKE :keywords OR ' .
                'i.name' . $languageIndex . ' LIKE :keywords OR ' .
                'ir.comment LIKE :keywords'
            )->setParameter('keywords', '%' . $params['keywords'] . '%');
        }

        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $queryBuilder->andWhere('ir.cacheMaxRisk > :thresholds')
                ->setParameter('thresholds', $params['thresholds']);
        }

        $orderField = $params['order'] ?? 'maxRisk';
        $orderDirection = isset($params['order_direction'])
            && strtolower(trim($params['order_direction'])) !== 'asc' ? 'DESC' : 'ASC';

        switch ($orderField) {
            case 'instance':
                $queryBuilder->orderBy('i.name' . $languageIndex, $orderDirection);
                break;
            case 'auditOrder':
                $queryBuilder->orderBy('amv.position', $orderDirection);
                break;
            case 'c_impact':
                $queryBuilder->orderBy('i.c', $orderDirection);
                break;
            case 'i_impact':
                $queryBuilder->orderBy('i.i', $orderDirection);
                break;
            case 'd_impact':
                $queryBuilder->orderBy('i.d', $orderDirection);
                break;
            case 'threat':
                $queryBuilder->orderBy('t.label' . $languageIndex, $orderDirection);
                break;
            case 'vulnerability':
                $queryBuilder->orderBy('v.label' . $languageIndex, $orderDirection);
                break;
            case 'vulnerabilityRate':
                $queryBuilder->orderBy('ir.vulnerabilityRate', $orderDirection);
                break;
            case 'threatRate':
                $queryBuilder->orderBy('ir.threatRate', $orderDirection);
                break;
            case 'targetRisk':
                $queryBuilder->orderBy('ir.cacheTargetedRisk', $orderDirection);
                break;
            default:
            case 'maxRisk':
                $queryBuilder->orderBy('ir.cacheMaxRisk', $orderDirection);
                break;
        }
        if ($params['order'] !== 'instance') {
            $queryBuilder->addOrderBy('i.name' . $languageIndex, Criteria::ASC);
        }
        $queryBuilder->addOrderBy('t.code', Criteria::ASC)
            ->addOrderBy('v.code', Criteria::ASC);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByInstanceAndInstanceRiskRelations(
        InstanceSuperClass $instance,
        InstanceRiskSuperClass $instanceRisk
    ) {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('ir')
            ->where('ir.instance = :instance')
            ->setParameter('instance', $instance);

        if ($instanceRisk->getAmv() !== null) {
            $queryBuilder->andWhere('ir.amv = :amv')->setParameter('amv', $instanceRisk->getAmv());
        }

        $queryBuilder
            ->andWhere('ir.threat = :threat')
            ->andWhere('ir.vulnerability = :vulnerability')
            ->setParameter('threat', $instanceRisk->getThreat())
            ->setParameter('vulnerability', $instanceRisk->getVulnerability());

        if ($instanceRisk->isSpecific()) {
            $queryBuilder->andWhere('ir.specific = ' . InstanceRiskSuperClass::TYPE_SPECIFIC);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
