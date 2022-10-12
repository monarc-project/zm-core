<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\Model;

class ModelTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = Model::class)
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
        $qb = $this->getRepository()->createQueryBuilder('m');

        return $qb
            ->select()
            ->where($qb->expr()->in('m.anr', $anrIds))
            ->getQuery()
            ->getResult();
    }
}
