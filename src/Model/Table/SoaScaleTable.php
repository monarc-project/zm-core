<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\SoaScale;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class SoaScaleTable
 * @package Monarc\Core\Model\Table
 */
class SoaScaleTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, SoaScale::class, $connectedUserService);
    }

    /**
     * @return SoaScaleSuperClass|null
     */
    public function findByAnr(AnrSuperClass $anr)
    {
        return $this->getRepository()->createQueryBuilder('ors')
            ->where('ors.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
