<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Table as DeprecatedTable;
use Monarc\Core\Service;

/**
 * Instance Service Factory
 *
 * Class InstanceServiceFactory
 * @package Monarc\Core\Service
 */
class InstanceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => DeprecatedTable\InstanceTable::class,
        'assetTable' => DeprecatedTable\AssetTable::class,
        'entity' => Entity\Instance::class,
        'instanceConsequenceEntity' => Entity\InstanceConsequence::class,
        'anrTable' => DeprecatedTable\AnrTable::class,
        'instanceConsequenceTable' => DeprecatedTable\InstanceConsequenceTable::class,
        'objectTable' => DeprecatedTable\MonarcObjectTable::class,
        'scaleTable' => DeprecatedTable\ScaleTable::class,
        'scaleImpactTypeTable' => DeprecatedTable\ScaleImpactTypeTable::class,
        'instanceRiskTable' => DeprecatedTable\InstanceRiskTable::class,
        'instanceRiskOpTable' => DeprecatedTable\InstanceRiskOpTable::class,
        'instanceConsequenceService' => Service\InstanceConsequenceService::class,
        'instanceRiskService' => Service\InstanceRiskService::class,
        'instanceRiskOpService' => Service\InstanceRiskOpService::class,
        'objectObjectService' => Service\ObjectObjectService::class,
        'objectExportService' => Service\ObjectExportService::class,
        'amvService' => Service\AmvService::class,
        'translateService' => Service\TranslateService::class,
        'configService' => ConfigService::class,
        'operationalRiskScalesExportService' => OperationalRiskScalesExportService::class,
    ];

    // TODO: A temporary solution to inject SharedEventManager. All the factories classes will be removed.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectObjectService = parent::__invoke($container, $requestedName, $options);

        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());

        return $objectObjectService;
    }
}
