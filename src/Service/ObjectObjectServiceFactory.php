<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;

/**
 * Object Object Service Factory
 *
 * Class ObjectObjectServiceFactory
 * @package Monarc\Core\Service
 */
class ObjectObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ObjectObjectTable',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'entity' => 'Monarc\Core\Model\Entity\ObjectObject',
        'childTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'fatherTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
    ];

    // TODO: A temporary solution to inject SharedEventManager. All the factories classes will be removed.
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectObjectService = parent::__invoke($container, $requestedName, $options);

        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());

        return $objectObjectService;
    }
}
