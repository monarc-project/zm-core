<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Measure Measure Service Factory
 *
 * Class MeasureMeasureServiceFactory
 * @package Monarc\Core\Service
 */
class MeasureMeasureServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\MeasureMeasureTable',
        'measureTable' => 'Monarc\Core\Model\Table\MeasureTable',
        'entity' => 'Monarc\Core\Entity\MeasureMeasure',
        'measureEntity' => 'Monarc\Core\Entity\Measure',
    ];
}
