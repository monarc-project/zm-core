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
use Monarc\Core\Model\Entity\Measure;
use Monarc\Core\Model\Entity\MeasureSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class MeasureTable
 * @package Monarc\Core\Model\Table
 */
class MeasureTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Measure::class, $connectedUserService);
    }

    public function findByUuid(string $uuid): ?Measure
    {
        $measure = $this->getRepository()
            ->createQueryBuilder('m')
            ->where('m.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($measure === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Measure::class, [$uuid]);
        }

        return $measure;
    }

    public function findByAnrAndUuid(AnrSuperClass $anr, string $uuid): ?MeasureSuperClass
    {
        return null;
    }

    public function saveEntity(MeasureSuperClass $measure, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($measure);
        if ($flushAll) {
            $em->flush();
        }
    }
}
