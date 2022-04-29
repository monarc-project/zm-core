<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Table;
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
        'table' => Table\InstanceTable::class,
        'assetTable' => Table\AssetTable::class,
        'entity' => Entity\Instance::class,
        'instanceConsequenceEntity' => Entity\InstanceConsequence::class,
        'anrTable' => Table\AnrTable::class,
        'amvTable' => Table\AmvTable::class,
        'instanceConsequenceTable' => Table\InstanceConsequenceTable::class,
        'objectTable' => Table\MonarcObjectTable::class,
        'scaleTable' => Table\ScaleTable::class,
        'scaleImpactTypeTable' => Table\ScaleImpactTypeTable::class,
        'instanceRiskTable' => Table\InstanceRiskTable::class,
        'instanceRiskOpTable' => Table\InstanceRiskOpTable::class,
        'instanceConsequenceService' => Service\InstanceConsequenceService::class,
        'instanceRiskService' => Service\InstanceRiskService::class,
        'instanceRiskOpService' => Service\InstanceRiskOpService::class,
        'objectObjectService' => Service\ObjectObjectService::class,
        'objectExportService' => Service\ObjectExportService::class,
        'amvService' => Service\AmvService::class,
        'translateService' => Service\TranslateService::class,
        'configService' => ConfigService::class,
        'operationalRiskScalesExportService' => OperationalRiskScalesExportService::class,
        'anrMetadatasOnInstancesExportService' => AnrMetadatasOnInstancesExportService::class,
    ];

    // TODO: A temporary solution to inject SharedEventManager. All the factories classes will be removed.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectObjectService = parent::__invoke($container, $requestedName, $options);

        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());

        return $objectObjectService;
    }
}
