<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Theme Service Factory
 *
 * Class ThemeServiceFactory
 * @package MonarcCore\Service
 */
class ThemeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ThemeTable',
        'entity' => 'MonarcCore\Model\Entity\Theme',
    ];
}