<?php
namespace Monarc\Core\Storage;

use Countable;
use DateTime;
use Monarc\Core\Model\Entity\UserToken;

use Monarc\Core\Model\Table\UserTokenTable;
use Zend\Authentication\Storage\StorageInterface;

class Authentication implements StorageInterface
{
    private const DEFAULT_TTL = 20;

    /** @var UserTokenTable */
    private $userTokenTable;

    /** @var int */
    private $authTtl;

    public function __construct(UserTokenTable $userTokenTable, array $config = [])
    {
        $this->userTokenTable = $userTokenTable;
        $this->authTtl = $config['monarc']['ttl'] ?? static::DEFAULT_TTL;
    }

    public function addItem($key, $value)
    {
        $this->clearItems();
        if (!$this->hasItem($key) && !empty($value)) {
            $tt = new UserToken();
            $tt->exchangeArray([
                'token' => $key,
                'user' => $value,
                'dateEnd' => new DateTime(sprintf('+%d min', $this->authTtl)),
            ]);
            $this->userTokenTable->save($tt);

            return true;
        }

        return false;
    }

    public function getItem($key)
    {
        if ($this->hasItem($key)) {
            return $this->userTokenTable->getRepository()->findOneByToken($key);
        }

        return null;
    }

    public function replaceItem($key, $value)
    {
        $item = $this->getItem($key);
        if ($item) {
            $item->set('dateEnd', new DateTime(sprintf('+%d min', $this->authTtl)));
            $this->userTokenTable->save($item);

            return true;
        }

        return false;
    }

    public function hasItem($key)
    {
        $token = $this->userTokenTable->getRepository()->findOneByToken($key);
        if (null === $token) {
            return false;
        }
        if (is_scalar($token)) {
            return $token !== '';
        }
        if (is_object($token) && !$token instanceof Countable && method_exists($token, '__toString')) {
            return (string)$token !== '';
        }
        if ($token instanceof Countable || is_array($token)) {
            return count($token) > 0;
        }

        return true;
    }

    public function removeItem($key)
    {
        $item = $this->getItem($key);
        if ($item) {
            $this->userTokenTable->delete($item->get('id'));

            return true;
        }

        return false;
    }

    protected function clearItems()
    {
        $tokenIds = $this->userTokenTable->getRepository()->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.dateEnd < :d')
            ->setParameter(':d', date('Y-m-d H:i:s'))
            ->getQuery()->getResult();

        foreach ($tokenIds as $i) {
            $this->userTokenTable->delete($i['id']);
        }
    }

    public function isEmpty(){}
    public function read(){}
    public function write($contents){}
    public function clear(){}
}
