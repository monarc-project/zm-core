<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Anr Object Service Factory
 *
 * Class AnrObjectServiceFactory
 * @package MonarcCore\Service
 */
class AnrObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'entity' => 'MonarcCore\Model\Entity\MonarcObject',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'objectService' => 'MonarcCore\Service\ObjectService'
    ];
}
