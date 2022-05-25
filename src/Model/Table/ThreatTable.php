<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Threat;
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

    public function findByUuid(string $uuid): Threat
    {
        $threat = $this->getRepository()->createQueryBuilder('t')
            ->where('t.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($threat === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Threat::class, [$uuid]);
        }

        return $threat;
    }
}
