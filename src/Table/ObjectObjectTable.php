<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\ObjectObject;
use Monarc\Core\Model\Entity\ObjectObjectSuperClass;

class ObjectObjectTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = ObjectObject::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    // TODO: check if we should move the methods to the FO.

    /**
     * TODO: due to complications of double fields relation on FO side (uuid + anr) we cant simply add self-reference.
     * When it's refactored, we can change to $object->getParents() and $object->getChildren().
     *
     * @param string[] $uuids
     *
     * @return ObjectObjectSuperClass[]
     */
    public function findByParentsUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o');

        return $queryBuilder
            ->innerJoin('o.parent', 'op')
            ->where($queryBuilder->expr()->in('op.uuid', ':parentUuids'))
            ->setParameter('parentUuids', $uuids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $uuids
     *
     * @return ObjectObjectSuperClass[]
     */
    public function findByChildrenUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o');

        return $queryBuilder
            ->innerJoin('o.child', 'oc')
            ->where($queryBuilder->expr()->in('oc.uuid', ':childrenUuids'))
            ->setParameter('childrenUuids', $uuids)
            ->getQuery()
            ->getResult();
    }
}
