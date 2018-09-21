<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class MonarcObjectServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
class MonarcObjectServiceModelTable extends AbstractServiceModelTable
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = parent::createService($serviceLocator);
        if ($instance !== false) {
            $instance->setObjectObjectTable($serviceLocator->get('\MonarcCore\Model\Table\ObjectObjectTable'));
        }
        return $instance;
    }
}