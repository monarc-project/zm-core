<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\Theme;
use Monarc\Core\Model\Entity\ThemeSuperClass;

class ThemeTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = Theme::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByLabel(string $labelKey, string $labelValue): ?ThemeSuperClass
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->andWhere('t.' . $labelKey . ' = :' . $labelKey)
            ->setParameter($labelKey, $labelValue)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
