<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class MonarcObjectServiceModelTable
 * @package Monarc\Core\Service\Model\Table
 */
class MonarcObjectServiceModelTable extends AbstractServiceModelTable
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = parent::createService($serviceLocator);
        if ($instance !== false) {
            // TODO: check why do we need to set it here.
            $instance->setObjectObjectTable($serviceLocator->get('\Monarc\Core\Model\Table\ObjectObjectTable'));
        }
        return $instance;
    }
}
