<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ObjectServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
class ObjectServiceModelTable extends AbstractServiceModelTable
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