<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserToken
 *
 * @ORM\Table(name="user_tokens", uniqueConstraints={@ORM\UniqueConstraint(name="token", columns={"token"})})
 * @ORM\Entity
 */
class UserToken extends UserTokenSuperClass
{

}
