<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Model Object Service Factory
 *
 * Class ModelObjectServiceFactory
 * @package Monarc\Core\Service
 */
class ModelObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'entity' => 'Monarc\Core\Model\Entity\MonarcObject',
        'assetTable' => 'Monarc\Core\Model\Table\AssetTable',
        'categoryTable' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'sourceTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
    ];
}
