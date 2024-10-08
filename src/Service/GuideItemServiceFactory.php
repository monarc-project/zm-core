<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Guide Item Service Factory
 *
 * Class GuideItemServiceFactory
 * @package Monarc\Core\Service
 */
class GuideItemServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\GuideItemTable',
        'entity' => 'Monarc\Core\Entity\GuideItem',
        'guideTable' => 'Monarc\Core\Model\Table\GuideTable',
    ];
}
