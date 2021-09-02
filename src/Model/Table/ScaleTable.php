<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Scale;
use Monarc\Core\Model\Entity\ScaleSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ScaleTable
 * @package Monarc\Core\Model\Table
 */
class ScaleTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Scale::class, $connectedUserService);
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

    public function saveEntity(ScaleSuperClass $scale, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($scale);
        if ($flushAll) {
            $em->flush();
        }
    }
}
