<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Model\Entity\AnrSuperClass;

/**
 * Class SoaScaleTable
 * @package Monarc\Core\Model\Table
 */
class SoaScaleCommentTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, SoaScaleComment::class, $connectedUserService);
    }

    /**
     * @return SoaScaleComment[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('orsc')
            ->where('orsc.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
