<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Role Service
 *
 * Class RoleService
 * @package MonarcCore\Service
 */
class RoleService extends AbstractService
{
    protected $config;

    /**
     * @inheritdoc
     */
    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        $config = $this->config;
        $roles = $config['roles'];

        return count($roles);
    }

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $roleCollection = [];

        $config = $this->config;
        $roles = $config['roles'];
        foreach ($roles as $role => $permissions) {
            $roleCollection[] = ['name' => $role];
        }

        return $roleCollection;
    }
}