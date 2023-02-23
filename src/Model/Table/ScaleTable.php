<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Scale;
use Monarc\Core\Model\Entity\ScaleSuperClass;
use Monarc\Core\Service\ConnectedUserService;

class ScaleTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Scale::class, $connectedUserService);
    }

    public function findById(int $id): ScaleSuperClass
    {
        $scale = $this->getRepository()->find($id);
        if ($scale === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $scale;
    }

    /**
     * @return ScaleSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('s')
            ->where('s.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    public function findByAnrAndType(AnrSuperClass $anr, int $type): ScaleSuperClass
    {
        $scale = $this->getRepository()
            ->createQueryBuilder('s')
            ->where('s.anr = :anr')
            ->andWhere('s.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($scale === null) {
            throw new EntityNotFoundException(
                sprintf('Scale of type "%d" doesn\'t exist in anr ID: "%d"', $type, $anr->getId())
            );
        }

        return $scale;
    }

    public function saveEntity(ScaleSuperClass $scale, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($scale);
        if ($flushAll) {
            $em->flush();
        }
    }
}
