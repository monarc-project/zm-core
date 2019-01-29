<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
                return new \Doctrine\Common\Cache\ChainCache([$apcCache, $arrayCache]);
            } elseif (extension_loaded('apcu')) {
                $apcuCache = new \Doctrine\Common\Cache\ApcuCache();
                return new \Doctrine\Common\Cache\ChainCache([$apcuCache, $arrayCache]);
            }
            // TODO: untested / add param for memchache(d) host & port
            /*elseif(extension_loaded('memcache')){
                $memcache = new \Memcache();
                if($memcache->connect('localhost', 11211)){
                    $cache = new \Doctrine\Common\Cache\MemcacheCache();
                    $cache->setMemcache($mem);
                    return new \Doctrine\Common\Cache\ChainCache([$cache,$arrayCache]);
                }
            }elseif(extension_loaded('memcached')){
                $memcache = new \Memcached();
                if($memcache->connect('localhost', 11211)){
                    $cache = new \Doctrine\Common\Cache\MemcachedCache();
                    $cache->setMemcached($mem);
                    return new \Doctrine\Common\Cache\ChainCache([$cache,$arrayCache]);
                }
            }*/
        }
        return $arrayCache;
    }
}