<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monarc\Core\Model\DbCli;

/**
 * User
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 */
class User extends UserSuperClass
{
    // TODO: get rid of the dependency from entities classes.
    protected $ressources = ['setDbAdapter' => DbCli::class];

    // TODO: When we remove all the super classes the entities fields goes here.
    public function getId(): int
    {
        return $this->id;
    }
}
