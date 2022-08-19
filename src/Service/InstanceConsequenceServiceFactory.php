<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Table;
use Monarc\Core\Model\Entity\InstanceConsequence;

/**
 * Instance Consequence Service Factory
 *
 * Class InstanceConsequenceServiceFactory
 * @package Monarc\Core\Service
 */
class InstanceConsequenceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => Table\InstanceConsequenceTable::class,
        'entity' => InstanceConsequence::class,
        'anrTable' => Table\AnrTable::class,
        'instanceTable' => Table\InstanceTable::class,
        'scaleTable' => Table\ScaleTable::class,
        'scaleImpactTypeTable' => Table\ScaleImpactTypeTable::class,
        'scaleCommentTable' => Table\ScaleCommentTable::class,
    ];

    // TODO: A temporary solution to inject SharedEventManager. All the factories classes will be removed.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectObjectService = parent::__invoke($container, $requestedName, $options);

        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());

        return $objectObjectService;
    }
}
