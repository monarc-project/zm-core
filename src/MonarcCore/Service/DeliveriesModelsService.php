<?php
namespace MonarcCore\Service;

/**
 * DocModels Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class DeliveriesModelsService extends AbstractService
{
    protected $filterColumns = [
        //'category',
        //'description',
    ];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        $class = $this->get('entity');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        /** @var AnrTable $table */
        $table = $this->get('table');

        return $table->save($entity, $last);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \Exception('Entity does not exist', 412);
        }

        $this->filterPostFields($data, $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        if (isset($data['description'])) {
            $data['description' . $this->getLanguage()] = $data['description'];
            unset($data['description']);
        }

        $entity = $this->get('table')->getEntity($id);
        $entity->setLanguage($this->getLanguage());

        foreach ($this->dependencies as $dependency) {
            if (!isset($data[$dependency])) {
                $data[$dependency] = $entity->$dependency->id;
            }
        }

        $entity->exchangeArray($data, true);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }
}