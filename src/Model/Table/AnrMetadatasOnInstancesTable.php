<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstances;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstancesSuperClass;

class AnrMetadatasOnInstancesTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = AnrMetadatasOnInstances::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return AnrMetadatasOnInstancesSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('amoi')
            ->where('amoi.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
