<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Anr Object Service Factory
 *
 * Class AnrObjectServiceFactory
 * @package Monarc\Core\Service
 */
class AnrObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'entity' => 'Monarc\Core\Model\Entity\MonarcObject',
        'objectObjectTable' => 'Monarc\Core\Model\Table\ObjectObjectTable',
        'objectService' => 'Monarc\Core\Service\ObjectService'
    ];
}
