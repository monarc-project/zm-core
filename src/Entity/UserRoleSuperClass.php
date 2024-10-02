<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Entity\Traits\CreateEntityTrait;

/**
 * User Role
 *
 * @ORM\Table(name="users_roles")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class UserRoleSuperClass
{
    use CreateEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var UserSuperClass
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist", "remove"}, inversedBy="roles")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=true)
     */
    protected $role;

    public function __construct(UserSuperClass $user, string $role)
    {
        $this->role = $role;
        $this->user = $user;
        $this->creator = $user->getCreator();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser(): UserSuperClass
    {
        return $this->user;
    }

    public function setUser(UserSuperClass $user): self
    {
        $this->user = $user;
        $user->addRole($this);

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public static function getAvailableRoles(): array
    {
        return [];
    }
}
