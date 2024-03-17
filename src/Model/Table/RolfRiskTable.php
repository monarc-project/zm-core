<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Model\Db;
use Monarc\Core\Entity\RolfRisk;
use Monarc\Core\Entity\RolfRiskSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class RolfRiskTable
 * @package Monarc\Core\Model\Table
 */
class RolfRiskTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, RolfRisk::class, $connectedUserService);
    }

    /**
     * @throws NonUniqueResultException
     * @throws EntityNotFoundException
     */
    public function findById(int $id): RolfRiskSuperClass
    {
        $rolfRisk = $this->getRepository()
            ->createQueryBuilder('rr')
            ->where('rr.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($rolfRisk === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $rolfRisk;
    }

    public function saveEntity(RolfRiskSuperClass $rolfRisk, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($rolfRisk);
        if ($flushAll) {
            $em->flush();
        }
    }

    public function deleteEntity(RolfRiskSuperClass $rolfRisk, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($rolfRisk);
        if ($flush) {
            $em->flush();
        }
    }
}
