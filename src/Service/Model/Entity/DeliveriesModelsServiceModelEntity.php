<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Entity;

use Monarc\Core\Model\DbCli;

/**
 * Class DeliveriesModelsServiceModelEntity
 * @package Monarc\Core\Service\Model\Entity
 */
class DeliveriesModelsServiceModelEntity extends AbstractServiceModelEntity
{
    protected $ressources = [
        'setDbAdapter' => DbCli::class,
    ];
}
