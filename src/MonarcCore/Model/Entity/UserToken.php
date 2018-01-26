<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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