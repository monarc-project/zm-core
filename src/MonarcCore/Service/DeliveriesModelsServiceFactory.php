<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Doc Models Service Factory
 *
 * Class DeliveriesModelsServiceFactory
 * @package MonarcCore\Service
 */
class DeliveriesModelsServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\DeliveriesModelsTable',
        'entity' => 'MonarcCore\Model\Entity\DeliveriesModels',
    ];
}