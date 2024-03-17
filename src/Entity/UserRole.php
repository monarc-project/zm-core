<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_roles")
 * @ORM\Entity
 */
class UserRole extends UserRoleSuperClass
{
    public const SUPER_ADMIN = 'superadmin';
    public const DB_ADMIN = 'dbadmin';
    public const SYS_ADMIN = 'sysadmin';
    public const ACC_ADMIN = 'accadmin';

    public static function getAvailableRoles(): array
    {
        return [static::SUPER_ADMIN, static::DB_ADMIN, static::SYS_ADMIN, static::ACC_ADMIN];
    }
}
