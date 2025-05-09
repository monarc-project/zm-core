<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class UserSuperClass
{
    use Traits\CreateEntityTrait;
    use Traits\UpdateEntityTrait;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const CREATOR_SYSTEM = 'System';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_start", type="date", nullable=true)
     */
    protected $dateStart;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_end", type="date", nullable=true)
     */
    protected $dateEnd;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    protected $status = self::STATUS_ACTIVE;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=false)
     */
    protected $firstname = '';

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=false)
     */
    protected $lastname = '';

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    protected $password;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_two_factor_enabled", type="boolean", options={"default":false})
     */
    protected $isTwoFactorAuthEnabled = false;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_key", type="string", length=255, options={"default":""})
     */
    protected $secretKey = '';

    /**
     * @var array
     *
     * @ORM\Column(name="recovery_codes", type="array", length=250, options={"default":""})
     */
    protected $recoveryCodes = [];

    /**
     * @var integer
     *
     * @ORM\Column(name="language", type="integer", precision=0, scale=0, nullable=false)
     */
    protected $language = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="mosp_api_key", type="string", length=255, nullable=false)
     */
    protected $mospApiKey;

    /**
     * @var UserTokenSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="UserToken", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $userTokens;

    /**
     * @var PasswordTokenSuperClass[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PasswordToken", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $passwordTokens;

    /**
     * @var ArrayCollection|UserRoleSuperClass[]
     *
     * @ORM\OneToMany(targetEntity="UserRole", orphanRemoval=true, mappedBy="user", cascade={"persist", "remove"})
     */
    protected $roles;

    public function __construct(array $data)
    {
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'];
        $this->email = $data['email'];
        if (isset($data['password'])) {
            $this->setPassword($data['password']);
        }
        $this->language = $data['language'] ?? 1;
        $this->mospApiKey = $data['mospApiKey'] ?? '';
        $this->status = isset($data['status']) ? (int)$data['status'] : self::STATUS_ACTIVE;
        $this->creator = $data['creator'];
        $this->setRoles($data['role']);
        $this->userTokens = new ArrayCollection();
        $this->passwordTokens = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    public function resetPassword(): self
    {
        $this->password = '';

        return $this;
    }

    public function setLanguage(int $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): int
    {
        return $this->language;
    }

    public function getUserTokens()
    {
        return $this->userTokens;
    }

    public function addUserToken(UserTokenSuperClass $userToken): self
    {
        if (!$this->userTokens->contains($userToken)) {
            $this->userTokens->add($userToken);
            $userToken->setUser($this);
        }

        return $this;
    }

    public function getPasswordTokens()
    {
        return $this->passwordTokens;
    }

    public function addPasswordToken(PasswordTokenSuperClass $passwordToken): self
    {
        if (!$this->passwordTokens->contains($passwordToken)) {
            $this->passwordTokens->add($passwordToken);
            $passwordToken->setUser($this);
        }

        return $this;
    }

    abstract protected function createRole(string $role): UserRoleSuperClass;

    public function setRoles(array $roles): self
    {
        $this->roles = new ArrayCollection();
        foreach ($roles as $role) {
            $this->addRole($this->createRole($role));
        }

        return $this;
    }

    public function getRolesArray(): array
    {
        $roles = [];
        foreach ($this->roles as $role) {
            $roles[] = $role->getRole();
        }

        return $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function addRole(UserRoleSuperClass $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->setUser($this);
        }

        return $this;
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getRole() === $roleName) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMospApiKey(): ?string
    {
        return $this->mospApiKey;
    }

    public function setMospApiKey(string $mospApiKey): self
    {
        $this->mospApiKey = $mospApiKey;

        return $this;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getRecoveryCodes(): array
    {
        return !empty($this->recoveryCodes) ? $this->recoveryCodes : [];
    }

    public function setRecoveryCodes(array $recoveryCodes): self
    {
        $this->recoveryCodes = $recoveryCodes;

        return $this;
    }

    public function createRecoveryCodes(array $recoveryCodes): self
    {
        $this->recoveryCodes = array_map('password_hash', $recoveryCodes, [PASSWORD_BCRYPT]);

        return $this;
    }

    public function setTwoFactorAuthEnabled(bool $isTwoFactorAuthEnabled): self
    {
        $this->isTwoFactorAuthEnabled = $isTwoFactorAuthEnabled;

        return $this;
    }

    public function isTwoFactorAuthEnabled(): bool
    {
        return $this->isTwoFactorAuthEnabled;
    }

    public function isSystemUser(): bool
    {
        return $this->creator === static::CREATOR_SYSTEM;
    }
}
