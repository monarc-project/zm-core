<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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