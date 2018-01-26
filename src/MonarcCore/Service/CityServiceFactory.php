<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * City Service Factory
 *
 * Class CityServiceFactory
 * @package MonarcCore\Service
 */
class CityServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\CityTable',
        'entity' => 'MonarcCore\Model\Entity\City',
    ];
}
