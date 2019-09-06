<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * User profile Service
 *
 * Class UserProfileService
 * @package Monarc\Core\Service
 */
class UserProfileService extends AbstractService
{
    /**
     * TODO: change the method to manipulate with User object.
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
        if (!empty($data['new'])
            && !empty($data['confirm'])
            && !empty($data['old'])
            && $data['new'] === $data['old']
            && password_verify($data['confirm'], $user['password'])
        ) {
            $entity->exchangeArray(['password' => $data['new']]);
        } else {
            $entity->exchangeArray($data);
        }
        $this->get('table')->save($entity);

        return ['status' => 'ok'];
    }
}
