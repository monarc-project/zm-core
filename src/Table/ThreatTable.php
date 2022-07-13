<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\Threat;

class ThreatTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = Threat::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return Threat[]
     */
    public function findByMode(int $mode): array
    {
        return $this->getRepository()
            ->createQueryBuilder('t')
            ->where('t.mode = :mode')
            ->setParameter(':mode', $mode)
            ->getQuery()
            ->getResult();
    }
}
