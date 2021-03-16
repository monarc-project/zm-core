<?php
namespace Monarc\Core\Storage;

use DateTime;
use Laminas\Authentication\Storage\StorageInterface;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\UserToken;
use Monarc\Core\Model\Table\UserTokenTable;

class Authentication implements StorageInterface
{
    private const DEFAULT_TTL = 20;

    /** @var UserTokenTable */
    private $userTokenTable;

    /** @var int */
    private $authTtl;

    public function __construct(UserTokenTable $userTokenTable, array $config)
    {
        $this->userTokenTable = $userTokenTable;
        $this->authTtl = $config['monarc']['ttl'] ?? static::DEFAULT_TTL;
    }

    public function addUserToken(string $token, UserSuperClass $user): bool
    {
        $this->clearUserTokens($user);

        if (!$this->hasUserToken($token)) {
            $userToken = (new UserToken())
                ->setUser($user)
                ->setToken($token)
                ->setDateEnd(new DateTime(sprintf('+%d min', $this->authTtl)));

            $this->userTokenTable->saveEntity($userToken);

            return true;
        }

        return false;
    }

    public function getUserToken(string $token): ?UserToken
    {
        return $this->userTokenTable->findByToken($token);
    }

    public function refreshUserToken(UserToken $userToken): void
    {
        $userToken->setDateEnd(new DateTime(sprintf('+%d min', $this->authTtl)));

        $this->userTokenTable->saveEntity($userToken);
    }

    public function hasUserToken($key): bool
    {
        return $this->userTokenTable->findByToken($key) !== null;
    }

    public function removeUserToken(string $token)
    {
        $userToken = $this->getUserToken($token);
        if ($userToken !== null) {
            $this->userTokenTable->deleteEntity($userToken);

            return true;
        }

        return false;
    }

    protected function clearUserTokens(UserSuperClass $user): void
    {
        $tokens = $this->userTokenTable->findByUser($user);
        foreach ($tokens as $token) {
            $this->userTokenTable->deleteEntity($token, false);
        }

        $this->userTokenTable->getDb()->flush();
    }

    public function isEmpty(){}
    public function read(){}
    public function write($contents){}
    public function clear(){}
}
