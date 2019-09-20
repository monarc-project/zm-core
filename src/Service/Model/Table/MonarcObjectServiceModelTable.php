<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Table;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\Table\ObjectObjectTable;

/**
 * Class MonarcObjectServiceModelTable
 * @package Monarc\Core\Service\Model\Table
 */
class MonarcObjectServiceModelTable extends AbstractServiceModelTable
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $instance = parent::__invoke($container, $requestedName, $options);

        $instance->setObjectObjectTable($container->get(ObjectObjectTable::class));

        return $instance;
    }
}
