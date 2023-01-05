<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Doctrine Cache Service Factory
 *
 * Class DoctrineCacheServiceFactory
 * @package Monarc\Core\Service
 */
class DoctrineCacheServiceFactory implements FactoryInterface
{
    /**
     * @return ArrayCache|ChainCache
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $arrayCache = new ArrayCache();

        if (extension_loaded('apcu')) {
            return new ChainCache([new ApcuCache(), $arrayCache]);
        }
        if (extension_loaded('apc')) {
            return new ChainCache([new ApcCache(), $arrayCache]);
        }

        return $arrayCache;
    }
}
