<?php
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
                $entity->exchangeArray(array('password' => $data['new']));
            }
        } else {
            $entity->exchangeArray($data);
        }
        $this->get('table')->save($entity);
        return array('status' => 'ok');
    }
}
