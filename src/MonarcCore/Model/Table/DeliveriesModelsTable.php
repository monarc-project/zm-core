<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class DeliveriesModelsTable
 * @package MonarcCore\Model\Table
 */
class DeliveriesModelsTable extends AbstractEntityTable
{
    /**
     * Delete
     *
     * @param $id
     * @param bool $last
     * @return bool
     */
    public function delete($id, $last = true)
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            $id = (int)$id;

            $entity = new $c();
            $entity->set('id', $id);
            $entity = $this->getDb()->fetch($entity);

            if (file_exists($entity->get('path'))) {
                unlink($entity->get('path'));
            }

            $this->getDb()->delete($entity, $last);
            return true;
        } else {
            return false;
        }
    }
}