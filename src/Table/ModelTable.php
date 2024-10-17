<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\Model;

class ModelTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = Model::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * Reset the current default model.
     * There should be only a single default model.
     */
    public function resetCurrentDefault(): void
    {
        /** @var Model $defaultModel */
        $defaultModel = $this->getRepository()->createQueryBuilder('m')
            ->where('isDefault = 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($defaultModel !== null) {
            $defaultModel->setIsDefault(false);
            $this->save($defaultModel);
        }
    }

    /**
     * @return Model[]
     */
    public function findByAnrIds(array $anrIds): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('m');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('m.anr', $anrIds))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Model[]
     */
    public function fundGenericsAndSpecificsByIds(array $specificModelsIds = []): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('m')
            ->where('m.isGeneric = 1')
            ->andWhere('m.anr IS NOT NULL');

        if (!empty($specificModelsIds)) {
            $queryBuilder->orWhere($queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('m.isGeneric', 0),
                $queryBuilder->expr()->in('m.id', array_map('\intval', $specificModelsIds))
            ));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
