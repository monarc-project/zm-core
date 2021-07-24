<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class InstanceRiskOpTable
 * @package Monarc\Core\Model\Table
 */
class InstanceRiskOpTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, InstanceRiskOp::class, $connectedUserService);
    }

    /**
     * @return InstanceRiskOpSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('oprisk')
            ->where('oprisk.anr = :anr')
            ->setParameter(':anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceRiskOp[]
     */
    public function getInstancesRisksOp(int $anrId, array $instancesIds, array $params = []): array
    {
        $qb = $this->getRepository()->createQueryBuilder('iro');

        if (empty($instancesIds)) {
            $instancesIds[] = 0;
        }
        $return = $qb
            ->select()
            ->where($qb->expr()->in('iro.instance', $instancesIds))
            ->andWhere('iro.anr = :anr ')
            ->setParameter(':anr', $anrId);

        if (isset($params['kindOfMeasure'])) {
            $return->andWhere('iro.kindOfMeasure = :kim')
                ->setParameter(':kim', $params['kindOfMeasure']);
        }

        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $return->andWhere('iro.cacheNetRisk > :cnr')
                ->setParameter(':cnr', $params['thresholds']);
        }

        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $anr = new \Monarc\FrontOffice\Model\Entity\Anr();
            $anr->setDbAdapter($this->getDb());
            $anr->set('id', $anrId);
            $anr = $this->getDb()->fetch($anr);
            if (!$anr) {
                throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
            }
            $l = $anr->get('language');

            $fields = [
                'riskCacheLabel' .$l,
                'riskCacheDescription' . $l,
                'comment'
            ];

            $query = [];
            foreach ($fields as $f) {
                $query[] = $qb->expr()->like('iro.' . $f, "'%" . $params['keywords'] . "%'");
            }
            $orX = $qb->expr()->orX();
            $orX->addMultiple($query);
            $return->andWhere($orX);
        }

        $params['order_direction'] = isset($params['order_direction']) && strtolower(trim($params['order_direction'])) != 'asc' ? 'DESC' : 'ASC';
        return $return->orderBy('iro.' . $params['order'], $params['order_direction'])
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): InstanceRiskOpSuperClass
    {
        /** @var InstanceRiskOpSuperClass|null $instanceRiskOp */
        $instanceRiskOp = $this->getRepository()->find($id);
        if ($instanceRiskOp === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $instanceRiskOp;
    }

    /**
     * @return InstanceRiskOpSuperClass[]
     */
    public function findByInstance(InstanceSuperClass $instance)
    {
        return $this->getRepository()
            ->createQueryBuilder('oprisk')
            ->where('oprisk.instance = :instance')
            ->setParameter('instance', $instance)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceRiskOpSuperClass[]
     */
    public function findByAnrAndInstance(AnrSuperClass $anr, InstanceSuperClass $instance)
    {
        return $this->getRepository()
            ->createQueryBuilder('oprisk')
            ->where('oprisk.anr = :anr')
            ->andWhere('oprisk.instance = :instance')
            ->setParameter('anr', $anr)
            ->setParameter('instance', $instance)
            ->getQuery()
            ->getResult();
    }

    public function saveEntity(InstanceRiskOpSuperClass $instanceRiskOp, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($instanceRiskOp);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(InstanceRiskOpSuperClass $operationalRisk, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($operationalRisk);
        if ($flush) {
            $em->flush();
        }
    }
}
