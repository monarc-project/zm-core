<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class AssetTable
 * @package Monarc\Core\Model\Table
 */
class AssetTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Asset::class, $connectedUserService);
    }

    public function findByUuid(string $uuid): ?Asset
    {
        return $this->getRepository()->createQueryBuilder('a')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveEntity(Asset $asset, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($asset);
        if ($flushAll) {
            $em->flush();
        }
    }
}
