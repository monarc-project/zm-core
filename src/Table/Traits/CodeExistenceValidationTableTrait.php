<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Traits;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Table\AbstractTable;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;

trait CodeExistenceValidationTableTrait
{
    public function doesCodeAlreadyExist(string $code, ?AnrSuperClass $anr = null, array $excludeFilter = []): bool
    {
        if (!$this instanceof UniqueCodeTableInterface
            || !is_subclass_of($this, AbstractTable::class)
        ) {
            throw new \LogicException(
                'The trait "CodeExistenceValidationTableTrait" is used in the wrong table class "'
                . \get_class($this) . '".'
            );
        }

        $queryBuilder = $this->getRepository()->createQueryBuilder('t')
            ->select('t.code')
            ->where('t.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1);
        if ($anr !== null) {
            $queryBuilder->andWhere('t.anr = :anr')->setParameter('anr', $anr);
        }
        if (!empty($excludeFilter)) {
            foreach ($excludeFilter as $field => $value) {
                $queryBuilder->andWhere('t.' . $field . ' <> :' . $field)->setParameter($field, $value);
            }
        }

        return (bool)$queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }
}
