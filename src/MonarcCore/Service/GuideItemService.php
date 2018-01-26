<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
