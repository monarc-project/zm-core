<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Used in the global.php config.
 */
class DoctrineCacheServiceFactory implements FactoryInterface
{
    /**
 * Doctrine Cache 2 removed its legacy cache-driver implementations. Doctrine
 * ORM Module 6 uses PSR-6 cache pools instead.
 *
 * @return CacheItemPoolInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new DoctrineArrayCachePool();
    }
}

/**
 * The in-memory PSR-6 replacement for Doctrine Common Cache's removed
 * ArrayCache. Cache contents are intentionally limited to the current PHP
 * process, matching the previous fallback behavior.
 */
final class DoctrineArrayCachePool implements CacheItemPoolInterface
{
    /** @var array<string, DoctrineArrayCacheItem> */
    private array $items = [];

    /** @var array<string, DoctrineArrayCacheItem> */
    private array $deferred = [];

    public function getItem($key)
    {
        $key = $this->validateKey($key);
        if (isset($this->deferred[$key])) {
            return clone $this->deferred[$key];
        }
        if (!isset($this->items[$key]) || $this->items[$key]->isExpired()) {
            unset($this->items[$key]);
            return new DoctrineArrayCacheItem($key);
        }

        return clone $this->items[$key];
    }

    public function getItems(array $keys = [])
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    public function clear()
    {
        $this->items = [];
        $this->deferred = [];
        return true;
    }

    public function deleteItem($key)
    {
        $key = $this->validateKey($key);
        unset($this->items[$key], $this->deferred[$key]);
        return true;
    }

    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof DoctrineArrayCacheItem) {
            return false;
        }
        $this->items[$item->getKey()] = clone $item;
        unset($this->deferred[$item->getKey()]);
        return true;
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        if (!$item instanceof DoctrineArrayCacheItem) {
            return false;
        }
        $this->deferred[$item->getKey()] = clone $item;
        return true;
    }

    public function commit()
    {
        foreach ($this->deferred as $item) {
            $this->save($item);
        }
        return true;
    }

    private function validateKey($key): string
    {
        if (!is_string($key) || $key === '') {
            throw new \InvalidArgumentException('Cache keys must be non-empty strings.');
        }
        return $key;
    }
}

final class DoctrineArrayCacheItem implements CacheItemInterface
{
    private mixed $value = null;
    private bool $hit = false;
    private ?DateTimeInterface $expiresAt = null;

    public function __construct(private string $key)
    {
    }

    public function getKey()
    {
        return $this->key;
    }

    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }

    public function isHit()
    {
        return $this->hit && !$this->isExpired();
    }

    public function set($value)
    {
        $this->value = $value;
        $this->hit = true;
        return $this;
    }

    public function expiresAt($expiration)
    {
        if ($expiration !== null && !$expiration instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('Cache expiration must be a DateTimeInterface or null.');
        }
        $this->expiresAt = $expiration;
        return $this;
    }

    public function expiresAfter($time)
    {
        if ($time === null) {
            return $this->expiresAt(null);
        }
        if (is_int($time)) {
            return $this->expiresAt((new DateTimeImmutable())->modify(sprintf('+%d seconds', $time)));
        }
        if ($time instanceof DateInterval) {
            return $this->expiresAt((new DateTimeImmutable())->add($time));
        }

        throw new \InvalidArgumentException('Cache TTL must be an integer, DateInterval, or null.');
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= new DateTimeImmutable();
    }
}
