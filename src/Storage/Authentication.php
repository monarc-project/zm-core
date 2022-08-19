<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Storage;

use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Laminas\Authentication\Storage\StorageInterface;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\UserTokenSuperClass;
use Monarc\Core\Table\UserTokenTable;

class Authentication implements StorageInterface
{
    private const DEFAULT_TTL = 20;

    private UserTokenTable $userTokenTable;

    private int $authTtl;

    public function __construct(UserTokenTable $userTokenTable, array $config)
    {
        $this->userTokenTable = $userTokenTable;
        $this->authTtl = (int)($config['monarc']['ttl'] ?? static::DEFAULT_TTL);
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addUserToken(string $token, UserSuperClass $user): bool
    {
        $this->clearUserTokens($user);

        if (!$this->hasUserToken($token)) {
            $entityName = $this->userTokenTable->getEntityName();
            /** @var UserTokenSuperClass $userToken */
            $userToken = new $entityName();
            $userToken
                ->setUser($user)
                ->setToken($token)
                ->setDateEnd(new DateTime(sprintf('+%d min', $this->authTtl)));

            $this->userTokenTable->save($userToken);

            return true;
        }

        return false;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getUserToken(string $token): ?UserTokenSuperClass
    {
        return $this->userTokenTable->findByToken($token);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function refreshUserToken(UserTokenSuperClass $userToken): void
    {
        $userToken->setDateEnd(new DateTime(sprintf('+%d min', $this->authTtl)));

        $this->userTokenTable->save($userToken);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function hasUserToken($key): bool
    {
        return $this->userTokenTable->findByToken($key) !== null;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeUserToken(string $token): bool
    {
        $userToken = $this->getUserToken($token);
        if ($userToken !== null) {
            $this->userTokenTable->remove($userToken);

            return true;
        }

        return false;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function clearUserTokens(UserSuperClass $user): void
    {
        $tokens = $this->userTokenTable->findByUser($user);
        foreach ($tokens as $token) {
            $this->userTokenTable->remove($token, false);
        }

        $this->userTokenTable->flush();
    }

    public function isEmpty(){}
    public function read(){}
    public function write($contents){}
    public function clear(){}
}
