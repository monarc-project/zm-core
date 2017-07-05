<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Rolf Category Service Factory
 *
 * Class RolfCategoryServiceFactory
 * @package MonarcCore\Service
 */
class RolfCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\RolfCategoryTable',
        'entity' => 'MonarcCore\Model\Entity\RolfCategory',
    ];
}