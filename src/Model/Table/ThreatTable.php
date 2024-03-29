<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Entity\ThreatSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ThreatTable
 * @package Monarc\Core\Model\Table
 */
class ThreatTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Threat::class, $connectedUserService);
    }

    /**
     * @return ThreatSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr = :anr')
            ->setParameter(':anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
