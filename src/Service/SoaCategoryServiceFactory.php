<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * SoaCategory Service Factory
 *
 * Class SoaCategoryServiceFactory
 * @package Monarc\Core\Service
 */
class SoaCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\SoaCategoryTable',
        'entity' => 'Monarc\Core\Entity\SoaCategory',
        'referentialTable' => 'Monarc\Core\Model\Table\ReferentialTable',
    ];
}
