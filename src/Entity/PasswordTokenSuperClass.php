<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Password Token
 *
 * @ORM\Table(name="passwords_tokens", indexes={
 *      @ORM\Index(name="user_id", columns={"user_id"})
 * }), uniqueConstraints={@ORM\UniqueConstraint(name="token", columns={"token"})}
 * @ORM\MappedSuperclass
 */
class PasswordTokenSuperClass
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     */
    protected $token;

    /**
     * @var UserSuperClass
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * })
     */
    protected $user;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_end", type="datetime", nullable=true)
     */
    protected $dateEnd;

    public function __construct(string $token, UserSuperClass $user, DateTime $dateEnd)
    {
        $this->token = $token;
        $this->user = $user;
        $this->dateEnd = $dateEnd;
    }

    public function getUser(): UserSuperClass
    {
        return $this->user;
    }
    public function setUser(UserSuperClass $user): self
    {
        $this->user = $user;
        $user->addPasswordToken($this);

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
