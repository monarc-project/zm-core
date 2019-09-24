<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Table;

use Interop\Container\ContainerInterface;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\PasswordToken;
use Monarc\Core\Model\Table\PasswordTokenTable;
use Monarc\Core\Service\ConnectedUserService;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class PasswordTokenServiceModelTable
 * @package Monarc\Core\Service\Model\Table
 */
class PasswordTokenServiceModelTable implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // TODO: Password tokens can be stored in for instance in redis by multiple reasons,
        //  one is lifetime of the token, second faster storage.
        return new PasswordTokenTable(
            $container->get(DbCli::class),
            // PasswordToken::class,
            $container->get(ConnectedUserService::class)
        );
    }
}
