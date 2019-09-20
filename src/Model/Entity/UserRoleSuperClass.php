<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * User Role
 *
 * @ORM\Table(name="users_roles")
 * @ORM\MappedSuperclass
 */
class UserRoleSuperClass
{
    public const SUPER_ADMIN_FO = 'superadminfo';
    public const USER_FO = 'userfo';

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

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    public function __construct(UserSuperClass $user, string $role)
    {
        $this->role = $role;
        $this->user = $user;
        $this->creator = $user->getCreator();
        $this->createdAt = new DateTime();
    }

    public function getUser(): UserSuperClass
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
