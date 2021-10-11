<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRisk;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class InstanceRiskTable
 * @package Monarc\Core\Model\Table
 */
class InstanceRiskTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, InstanceRisk::class, $connectedUserService);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): InstanceRiskSuperClass
    {
        /** @var InstanceRiskSuperClass|null $instanceRisk */
        $instanceRisk = $this->getRepository()->find($id);
        if ($instanceRisk === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $instanceRisk;
    }

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('ir')
            ->where('ir.anr = :anr')
            ->setParameter('anr', $anr)
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
            ->leftJoin('ir.instanceRiskOwner', 'iro')
            ->where('ir.anr = :anr')
            ->andWhere('ir.cacheMaxRisk >= -1')
            ->setParameter('anr', $anr);

        if (!empty($params['instanceIds'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('ir.instance', $params['instanceIds']));
        }

        if (!empty($params['amvs'])) {
            $amvIds = $params['amvs'];
            if (\is_string($amvIds)) {
                $amvIds = explode(',', trim($amvIds), ',');
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('ir.amv', $amvIds));
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
/*
        $queryParams = [];
        if ($context === AbstractEntity::BACK_OFFICE) {
            $sql = 'SELECT ' . implode(',', $arraySelect) . '
                FROM       instances_risks AS ir
                INNER JOIN instances i
                ON         ir.instance_id = i.id
                LEFT JOIN  amvs AS a
                ON         ir.amv_id = a.uuid
                INNER JOIN threats AS t
                ON         ir.threat_id = t.uuid
                INNER JOIN vulnerabilities AS v
                ON         ir.vulnerability_id = v.uuid
                INNER JOIN objects AS o
                ON         i.object_id = o.uuid
                LEFT JOIN  assets AS ass
                ON         ir.asset_id = ass.uuid
                LEFT JOIN instance_risk_owners AS iro
                ON         ir.owner_id = iro.id
                AND        ir.anr_id = iro.anr_id
                WHERE      ir.cache_max_risk >= -1';
        } else {
            $arraySelect[] = 'rec.recommendations';
            $sql = 'SELECT ' . implode(',', $arraySelect) . '
                FROM       instances_risks AS ir
                INNER JOIN instances i
                ON         ir.instance_id = i.id
                LEFT JOIN  amvs AS a
                ON         ir.amv_id = a.uuid
                AND        ir.anr_id = a.anr_id
                INNER JOIN threats AS t
                ON         ir.threat_id = t.uuid
                AND        ir.anr_id = t.anr_id
                INNER JOIN vulnerabilities AS v
                ON         ir.vulnerability_id = v.uuid
                AND        ir.anr_id = v.anr_id
                INNER JOIN objects AS o
                ON         i.object_id = o.uuid
                AND        i.anr_id = o.anr_id
                LEFT JOIN  assets AS ass
                ON         ir.asset_id = ass.uuid
                AND        ir.anr_id = ass.anr_id
                LEFT JOIN instance_risk_owners AS iro
                ON         ir.owner_id = iro.id
                AND        ir.anr_id = iro.anr_id
                LEFT JOIN  (SELECT rr.instance_risk_id, rr.anr_id,
                    GROUP_CONCAT(rr.recommandation_id) AS recommendations
                    FROM   recommandations_risks AS rr
                    GROUP BY rr.instance_risk_id) AS rec
                ON         ir.id = rec.instance_risk_id
                AND        ir.anr_id = rec.anr_id
                WHERE      ir.cache_max_risk >= -1
                AND        ir.anr_id = :anrid';
            $queryParams = [
                ':anrid' => $anr->getId(),
            ];
        }

        return array_values($lst);
*/
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

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function findByInstance(InstanceSuperClass $instance, bool $onlySpecific = false)
    {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('ir')
            ->where('ir.instance = :instance')
            ->setParameter('instance', $instance);

        if ($onlySpecific) {
            $queryBuilder->andWhere('ir.specific = ' . InstanceRiskSuperClass::TYPE_SPECIFIC);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function saveEntity(InstanceRiskSuperClass $instanceRisk, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($instanceRisk);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(InstanceRiskSuperClass $instanceRisk, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($instanceRisk);
        if ($flush) {
            $em->flush();
        }
    }
}
