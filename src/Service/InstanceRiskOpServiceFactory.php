<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Instance Risk Op Service Factory
 *
 * Class InstanceRiskOpServiceFactory
 * @package Monarc\Core\Service
 */
class InstanceRiskOpServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\InstanceRiskOpTable',
        'entity' => 'Monarc\Core\Model\Entity\InstanceRiskOp',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'rolfRiskTable' => 'Monarc\Core\Model\Table\RolfRiskTable',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
    ];
}
