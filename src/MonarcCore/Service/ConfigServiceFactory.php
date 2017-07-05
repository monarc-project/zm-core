<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Config Service Factory
 *
 * Class ConfigServiceFactory
 * @package MonarcCore\Service
 */
class ConfigServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'config' => 'Config',
    ];
}