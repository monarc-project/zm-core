<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Theme;
use Monarc\Core\Model\Entity\ThemeSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ThemeTable
 * @package Monarc\Core\Model\Table
 */
class ThemeTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Theme::class, $connectedUserService);
    }

    public function findByAnrIdAndLabel(?int $anrId, string $labelKey, string $labelValue): ?ThemeSuperClass
    {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('t')
            ->select('t');

        if ($anrId !== null) {
            $queryBuilder
                ->andWhere('t.anr = :anrId')
                ->setParameter('anrId', $anrId);
        }

        return $queryBuilder
            ->andWhere('t.' . $labelKey . ' = :' . $labelKey)
            ->setParameter($labelKey, $labelValue)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveEntity(ThemeSuperClass $theme, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($theme);
        if ($flushAll) {
            $em->flush();
        }
    }
}
