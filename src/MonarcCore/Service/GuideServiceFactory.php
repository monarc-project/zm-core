<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Guide Service Factory
 *
 * Class GuideServiceFactory
 * @package MonarcCore\Service
 */
class GuideServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\GuideTable',
        'entity' => 'MonarcCore\Model\Entity\Guide',
    ];
}