<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Password Token
 *
 * @ORM\Table(name="passwords_tokens", indexes={
 *      @ORM\Index(name="user_id", columns={"user_id"})
 * }), uniqueConstraints={@ORM\UniqueConstraint(name="token", columns={"token"})}
 * @ORM\MappedSuperclass
 */
class PasswordTokenSuperClass extends AbstractEntity
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
     * @var \MonarcCore\Model\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\User", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_end", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $dateEnd;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return PasswordToken
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
}

