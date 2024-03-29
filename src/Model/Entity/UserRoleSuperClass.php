<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\Entity\Traits\CreateEntityTrait;

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

    public const SUPER_ADMIN_FO = 'superadminfo';
    public const USER_FO = 'userfo';
    public const USER_ROLE_CEO = 'ceo';
    public const USER_ROLE_SYSTEM = 'system';

    /**
     * @var integer
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

    public function getUser(): UserSuperClass
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public static function getAvailableRoles(): array
    {
        return [static::SUPER_ADMIN_FO, static::USER_FO, static::USER_ROLE_CEO];
    }
}
