<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceMetadata;
use Monarc\Core\Model\Entity\InstanceMetadataSuperClass;

class InstanceMetadataTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceMetadata::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return InstanceMetadataSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('im')
            ->where('im.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
