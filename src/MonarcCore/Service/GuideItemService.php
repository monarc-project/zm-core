<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Guide Item Service
 *
 * Class GuideItemService
 * @package MonarcCore\Service
 */
class GuideItemService extends AbstractService
{
    protected $guideTable;
    protected $dependencies = ['guide'];

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];

        $entity = $this->get('entity');
        $entity->setDbAdapter($this->table->getDb());

        $entity->exchangeArray($data);

        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->table->getDb());
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $this->get('table')->delete($id);
    }

}
