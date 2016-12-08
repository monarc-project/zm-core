<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * User Token
 *
 * @ORM\Table(name="users_tokens", indexes={
 *      @ORM\Index(name="user_id", columns={"user_id"})
 * })
 * @ORM\MappedSuperclass
 */
class UserTokenSuperClass extends AbstractEntity
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
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\User")
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
}

