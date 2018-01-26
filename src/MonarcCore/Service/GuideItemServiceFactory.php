<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Guide Item Service Factory
 *
 * Class GuideItemServiceFactory
 * @package MonarcCore\Service
 */
class GuideItemServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\GuideItemTable',
        'entity' => 'MonarcCore\Model\Entity\GuideItem',
        'guideTable' => 'MonarcCore\Model\Table\GuideTable',
    ];
}