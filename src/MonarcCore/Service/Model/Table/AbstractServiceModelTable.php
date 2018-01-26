<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
abstract class AbstractServiceModelTable implements FactoryInterface
{
    protected $dbService = '\MonarcCore\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $class = str_replace('Service\\', '', substr(get_class($this), 0, -17)) . 'Table';
        if (class_exists($class)) {
            $instance = new $class($serviceLocator->get($this->dbService));
            $instance->setConnectedUser($serviceLocator->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());

            return $instance;
        } else {
            return false;
        }
    }
}
