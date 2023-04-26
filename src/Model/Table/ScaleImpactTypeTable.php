<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ScaleImpactType;
use Monarc\Core\Model\Entity\ScaleImpactTypeSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ScaleImpactTypeTable
 * @package Monarc\Core\Model\Table
 */
class ScaleImpactTypeTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, ScaleImpactType::class, $connectedUserService);
    }

    public function findById(int $id): ScaleImpactTypeSuperClass
    {
        /** @var ScaleImpactTypeSuperClass|null $scaleImpactType */
        $scaleImpactType = $this->getRepository()->find($id);
        if ($scaleImpactType === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $scaleImpactType;
    }

    /**
     * @return ScaleImpactTypeSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('sit')
            ->where('sit.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    public function saveEntity(ScaleImpactTypeSuperClass $scaleImpactType, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($scaleImpactType);
        if ($flushAll) {
            $em->flush();
        }
    }
}
