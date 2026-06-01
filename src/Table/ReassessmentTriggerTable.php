<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\ReassessmentTrigger;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class ReassessmentTriggerTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = ReassessmentTrigger::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return ReassessmentTrigger[]
     */
    public function findActiveOrdered(bool $includeInactive = false): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');
        if (!$includeInactive) {
            $queryBuilder->where('t.isActive = 1');
        }

        return $queryBuilder
            ->orderBy('t.position', Criteria::ASC)
            ->addOrderBy('t.id', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
