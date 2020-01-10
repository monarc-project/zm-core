<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class AnrTable
 * @package Monarc\Core\Model\Table
 */
class AnrTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Anr::class, $connectedUserService);
    }

    public function findByIds(array $ids)
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('a');

        return $queryBuilder->where($queryBuilder->expr()->in('a.id', array_map('\intval', $ids)))
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?AnrSuperClass
    {
        /** @var Anr|null $anr */
        $anr = $this->getRepository()->find($id);

        return $anr;
    }
}
