<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Doc Models Service Factory
 *
 * Class DeliveriesModelsServiceFactory
 * @package Monarc\Core\Service
 */
class DeliveriesModelsServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\DeliveriesModelsTable',
        'entity' => 'Monarc\Core\Model\Entity\DeliveriesModels',
    ];
}
