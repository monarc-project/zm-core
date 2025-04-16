<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\Asset;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Table\Traits\CodeExistenceValidationTableTrait;

class AssetTable extends AbstractTable implements UniqueCodeTableInterface
{
    use CodeExistenceValidationTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = Asset::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return Asset[]
     */
    public function findByMode(int $mode): array
    {
        return $this->getRepository()
            ->createQueryBuilder('a')
            ->where('a.mode = :mode')
            ->setParameter('mode', $mode)
            ->getQuery()
            ->getResult();
    }
}
