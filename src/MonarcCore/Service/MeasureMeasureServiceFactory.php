<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Measure Measure Service Factory
 *
 * Class MeasureMeasureServiceFactory
 * @package MonarcCore\Service
 */
class MeasureMeasureServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\MeasureMeasureTable',
        'entity' => 'MonarcCore\Model\Entity\MeasureMeasure',
    ];
}
