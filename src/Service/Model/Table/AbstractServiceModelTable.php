<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Table;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Db;
use Monarc\Core\Service\ConnectedUserService;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * // TODO: detach the class from all its children
 * Class AbstractServiceModelTable
 * @package Monarc\Core\Service\Model\Table
 */
abstract class AbstractServiceModelTable implements FactoryInterface
{
    protected $dbService = Db::class;

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $class = str_replace('Service\\', '', substr(get_class($this), 0, -17)) . 'Table';
        if (class_exists($class)) {
            //TODO: this factory class will be removed, it's just a temporary solution.
            $instance = new $class(
                $container->get($this->dbService),
                str_replace(array('\\Table', 'Table'), array('\\Entity', ''), $class),
                $container->get(ConnectedUserService::class)->getConnectedUser()
            );

            return $instance;
        }

        return false;
    }
}
