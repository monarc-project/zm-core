<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\OperationalRiskScale;

class OperationalRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, OperationalRiskScale::class);
    }
}
