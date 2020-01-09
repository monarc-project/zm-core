<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

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
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    protected $token;

    /**
     * @var UserSuperClass
     *
     * @ORM\ManyToOne(targetEntity="Monarc\Core\Model\Entity\User", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $user;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_end", type="datetime", precision=0, scale=0, nullable=true, unique=false)
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

    public function getToken(): string
    {
        return $this->token;
    }
}
