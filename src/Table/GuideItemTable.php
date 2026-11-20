<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\Guide;
use Monarc\Core\Entity\GuideItem;

class GuideItemTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, GuideItem::class);
    }

    /** @return GuideItem[] */
    public function findChildrenOfGuide(Guide $guide): array
    {
        return $this->getRepository()->findBy(['guide' => $guide], ['position' => 'ASC', 'id' => 'ASC']);
    }
}
