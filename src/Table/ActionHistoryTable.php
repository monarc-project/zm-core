<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2025 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\ActionHistory;
use Monarc\Core\Entity\ActionHistorySuperClass;

class ActionHistoryTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = ActionHistory::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return ActionHistorySuperClass[]
     */
    public function findByActionOrderByDate(string $action, int $limit): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('ah')
            ->where('ah.action = :action')
            ->setParameter('action', $action)
            ->orderBy('ah.createdAt', Criteria::DESC);
        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
