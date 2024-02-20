<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\ObjectObject;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class ObjectObjectTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = ObjectObject::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByParentObjectAndPosition(ObjectObject $parentObject, int $position): ?ObjectObject
    {
        return $this->getRepository()->createQueryBuilder('oo')
            ->where('oo.parent = :parentObject')
            ->andWhere('oo.position = :position')
            ->setParameter('parentObject', $parentObject)
            ->setParameter('position', $position)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
