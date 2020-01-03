<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Scale Comment Service Factory
 *
 * Class ScaleCommentServiceFactory
 * @package Monarc\Core\Service
 */
class ScaleCommentServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ScaleCommentTable',
        'entity' => 'Monarc\Core\Model\Entity\ScaleComment',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'Monarc\Core\Model\Table\ScaleImpactTypeTable',
        'scaleService' => 'Monarc\Core\Service\ScaleService',
        'scaleImpactTypeService' => 'Monarc\Core\Service\ScaleImpactTypeService',
    ];
}
