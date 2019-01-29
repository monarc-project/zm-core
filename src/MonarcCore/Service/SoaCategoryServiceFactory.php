<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * SoaCategory Service Factory
 *
 * Class SoaCategoryServiceFactory
 * @package MonarcCore\Service
 */
class SoaCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\SoaCategoryTable',
        'entity' => 'MonarcCore\Model\Entity\SoaCategory',
        'referentialTable' => 'MonarcCore\Model\Table\ReferentialTable',
    ];
}
