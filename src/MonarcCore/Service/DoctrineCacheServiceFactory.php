<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Doctrine Cache Service Factory
 *
 * Class DoctrineCacheServiceFactory
 * @package MonarcCore\Service
 */
class DoctrineCacheServiceFactory implements FactoryInterface
{
    protected $ressources;

    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\Common\Cache\ArrayCache|\Doctrine\Common\Cache\ChainCache
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $arrayCache = new \Doctrine\Common\Cache\ArrayCache();

        if (getenv('APPLICATION_ENV') == 'production') {
            if (extension_loaded('apc')) {
                $apcCache = new \Doctrine\Common\Cache\ApcCache();
                $chainCache = new \Doctrine\Common\Cache\ChainCache([$apcCache, $arrayCache]);
                return $chainCache;
            } elseif (extension_loaded('apcu')) {
                $apcuCache = new \Doctrine\Common\Cache\ApcuCache();
                $chainCache = new \Doctrine\Common\Cache\ChainCache([$apcuCache, $arrayCache]);
                return $chainCache;
            }
            // TODO: untested / add param for memchache(d) host & port
            /*elseif(extension_loaded('memcache')){
                $memcache = new \Memcache();
                if($memcache->connect('localhost', 11211)){
                    $cache = new \Doctrine\Common\Cache\MemcacheCache();
                    $cache->setMemcache($mem);
                    $chainCache = new \Doctrine\Common\Cache\ChainCache([$cache,$arrayCache]);
                    return $chainCache;
                }
            }elseif(extension_loaded('memcached')){
                $memcache = new \Memcached();
                if($memcache->connect('localhost', 11211)){
                    $cache = new \Doctrine\Common\Cache\MemcachedCache();
                    $cache->setMemcached($mem);
                    $chainCache = new \Doctrine\Common\Cache\ChainCache([$cache,$arrayCache]);
                    return $chainCache;
                }
            }*/
        }
        return $arrayCache;
    }
}