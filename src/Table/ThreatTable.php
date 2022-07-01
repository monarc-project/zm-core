<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Entity\ThreatSuperClass;

class ThreatTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = Threat::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByUuid(string $uuid): Threat
    {
        $threat = $this->getRepository()->createQueryBuilder('t')
            ->where('t.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($threat === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Threat::class, [$uuid]);
        }

        return $threat;
    }

    /**
     * @param string[] $uuids
     *
     * @return Threat[]
     */
    public function findByUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('t.uuid', $uuids))
            ->getQuery()
            ->getResult();
    }
}
