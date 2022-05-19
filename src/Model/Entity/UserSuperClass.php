<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits;

/**
 * TODO: move filter functionality to a filter class.
 * TODO: move exchangeArray functionality to a some validator class (what can guess from the first look)
 *
 * User Super Class
 *
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
     * @ORM\Column(name="status", type="smallint", nullable=true)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    protected $lastname;

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
    protected $isTwoFactorAuthEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_key", type="string", length=255, options={"default":""})
     */
    protected $secretKey;

    /**
     * @var ArrayCollection
     *
     * @ORM\Column(name="recovery_codes", type="array", length=250, options={"default":""})
     */
    protected $recoveryCodes;

    /**
     * @var integer
     *
     * @ORM\Column(name="language", type="integer", precision=0, scale=0, nullable=false)
     */
    protected $language = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="mosp_api_key", type="string", length=255, nullable=true)
     */
    protected $mospApiKey;

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
        $this->secretKey = '';
        $this->language = $data['language'];
        $this->mospApiKey = $data['mospApiKey'];
        $this->status = $data['status'] ?? self::STATUS_ACTIVE;
        $this->creator = $data['creator'];
        $this->setRoles($data['role']);
    }

    public function getId(): int
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

    abstract protected function createRole(string $role): UserRoleSuperClass;

    public function setRoles(array $roles): self
    {
        $this->roles = new ArrayCollection();
        foreach ($roles as $role) {
            $this->addRole($this->createRole($role));
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->roles as $role) {
            $roles[] = $role->getRole();
        }

        return $roles;
    }

    public function addRole(UserRoleSuperClass $role): self
    {
        $this->roles->add($role);

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

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(?string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getRecoveryCodes(): ?array
    {
        return $this->recoveryCodes;
    }

    public function setRecoveryCodes(?array $recoveryCodes): self
    {
        $this->recoveryCodes = $recoveryCodes;

        return $this;
    }

    public function createRecoveryCodes(?array $recoveryCodes): self
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
