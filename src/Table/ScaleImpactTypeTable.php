<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ScaleImpactType;
use Monarc\Core\Model\Entity\ScaleImpactTypeSuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class ScaleImpactTypeTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = ScaleImpactType::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return ScaleImpactTypeSuperClass[]
     */
    public function findByAnrIndexedByType(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('sit', 'sit.type')
            ->where('sit.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}