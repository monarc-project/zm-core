<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;

/**
 * Instance Service Factory
 *
 * Class InstanceServiceFactory
 * @package Monarc\Core\Service
 */
class InstanceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\InstanceTable',
        'assetTable' => 'Monarc\Core\Model\Table\AssetTable',
        'entity' => 'Monarc\Core\Model\Entity\Instance',
        'instanceConsequenceEntity' => 'Monarc\Core\Model\Entity\InstanceConsequence',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'amvTable' => 'Monarc\Core\Model\Table\AmvTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'Monarc\Core\Model\Table\InstanceConsequenceTable',
        'objectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
        'scaleCommentTable' => 'Monarc\Core\Model\Table\ScaleCommentTable',
        'scaleImpactTypeTable' => 'Monarc\Core\Model\Table\ScaleImpactTypeTable',
        'instanceConsequenceService' => 'Monarc\Core\Service\InstanceConsequenceService',
        'instanceRiskService' => 'Monarc\Core\Service\InstanceRiskService',
        'instanceRiskOpService' => 'Monarc\Core\Service\InstanceRiskOpService',
        'objectObjectService' => 'Monarc\Core\Service\ObjectObjectService',
        'objectExportService' => 'Monarc\Core\Service\ObjectExportService',
        'amvService' => 'Monarc\Core\Service\AmvService',
        'translateService' => 'Monarc\Core\Service\TranslateService',
    ];

    // TODO: A temporary solution to inject SharedEventManager. All the factories classes will be removed.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectObjectService = parent::__invoke($container, $requestedName, $options);

        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());

        return $objectObjectService;
    }
}
