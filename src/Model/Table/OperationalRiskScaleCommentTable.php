<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;

class OperationalRiskScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, OperationalRiskScaleComment::class);
    }
}
