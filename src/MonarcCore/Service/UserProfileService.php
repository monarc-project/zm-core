<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * User profile Service
 *
 * Class UserProfileService
 * @package MonarcCore\Service
 */
class UserProfileService extends AbstractService
{
    protected $securityService;

    /**
     * @inheritdoc
     */
    public function update($user, $data)
    {
        // unauthorized fields
        unset($data['dateStart']);
        unset($data['dateEnd']);
        unset($data['status']);

        $entity = $this->get('table')->getEntity($user['id']);
        $entity->setDbAdapter($this->get('table')->getDb());
        if (!empty($data['new'])) {
            if (!empty($data['confirm']) && !empty($data['old']) && $data['new'] == $data['old'] && $this->get('securityService')->verifyPwd($data['confirm'], $user->get('password'))) {
                $entity->exchangeArray(['password' => $data['new']]);
            }
        } else {
            $entity->exchangeArray($data);
        }
        $this->get('table')->save($entity);
        return ['status' => 'ok'];
    }
}