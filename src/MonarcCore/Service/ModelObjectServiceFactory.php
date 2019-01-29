<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Model Object Service Factory
 *
 * Class ModelObjectServiceFactory
 * @package MonarcCore\Service
 */
class ModelObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'entity' => 'MonarcCore\Model\Entity\MonarcObject',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'rolfTagTable' => '\MonarcCore\Model\Table\RolfTagTable',
        'sourceTable' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
    ];
}