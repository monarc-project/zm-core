<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\DeliveriesModels;

class DeliveriesModelsTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, DeliveriesModels::class);
    }

    public function countForCategory(int $category): int
    {
        return $this->getRepository()->count(['category' => $category]);
    }

    public function findDefaultTemplate(int $category, int $languageIndex): ?DeliveriesModels
    {
        $pathField = 't.path' . $languageIndex;

        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.category = :category')
            ->andWhere($pathField . ' IS NOT NULL')
            ->andWhere($pathField . " <> ''")
            ->setParameter('category', $category)
            ->orderBy('t.editable', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
