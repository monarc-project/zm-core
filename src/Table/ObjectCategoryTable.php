<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\ObjectCategory;
use Monarc\Core\Entity\ObjectCategorySuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class ObjectCategoryTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = ObjectCategory::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findPreviousCategory(ObjectCategorySuperClass $objectCategory): ?ObjectCategorySuperClass
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('oc');

        foreach ($objectCategory->getImplicitPositionRelationsValues() as $fieldName => $fieldValue) {
            if ($fieldValue !== null) {
                $queryBuilder
                    ->andWhere('oc.' . $fieldName . ' = :' . $fieldName)
                    ->setParameter($fieldName, $fieldValue);
            } else {
                $queryBuilder->andWhere('oc.' . $fieldName . ' IS NULL');
            }
        }
        $queryBuilder->andWhere('oc.position = :position')
            ->setParameter('position', $objectCategory->getPosition() - 1);

        return $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
