<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\RiskSource;

class RiskSourceTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = RiskSource::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return RiskSource[]
     */
    public function findByFilterParams(array $params = []): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('rs');

        if (array_key_exists('isActive', $params) && $params['isActive'] !== null) {
            $queryBuilder
                ->andWhere('rs.isActive = :isActive')
                ->setParameter('isActive', (bool)$params['isActive']);
        }

        if (!empty($params['label'])) {
            $queryBuilder
                ->andWhere('rs.label LIKE :label')
                ->setParameter('label', '%' . trim((string)$params['label']) . '%');
        }

        return $queryBuilder
            ->orderBy('rs.isDefault', Criteria::DESC)
            ->addOrderBy('rs.label', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
