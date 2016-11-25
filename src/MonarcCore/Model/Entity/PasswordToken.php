<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PasswordToken
 *
 * @ORM\Table(name="passwords_tokens", uniqueConstraints={@ORM\UniqueConstraint(name="token", columns={"token"})})
 * @ORM\Entity
 */
class PasswordToken extends PasswordTokenSuperClass
{

}
