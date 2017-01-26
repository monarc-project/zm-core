<?php
namespace MonarcCore\Service;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelObjectService extends AbstractService
{
    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;
    protected $sourceTable;
    protected $modelTable;

    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );
    protected $dependencies = ['asset', 'category', 'rolfTag', 'source', 'model'];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        if (!empty($data['id']) && !empty($data['model'])) {
            $obj = $this->get('table')->getEntity($data['id']);
            if (!$obj->get('model') && $obj->get('type') == 'bdc') {
                $model = $data['model'];
                $obj->setDbAdapter($this->get('table')->getDb());
                $data = $obj->getJsonArray(array(
                    'anr',
                    'category',
                    'asset',
                    'source',
                    'rolfTag',
                    'mode',
                    'scope',
                    'name1',
                    'name2',
                    'name3',
                    'name4',
                    'label1',
                    'label2',
                    'label3',
                    'label4',
                    'description1',
                    'description2',
                    'description3',
                    'description4',
                    'c',
                    'i',
                    'd',
                    'position',
                    'tokenImport',
                    'originalName',
                ));
                $data['category'] = $data['category']->get('id');
                $data['asset'] = $data['asset']->get('id');
                $data['rolfTag'] = $data['rolfTag']->get('id');
                $data['source'] = $obj->get('id');
                $data['type'] = 'anr';
                $data['model'] = $model;
                unset($data['creator']);
                unset($data['created_at']);
                unset($data['updater']);
                unset($data['updated_at']);
            }
            unset($data['id']);
        }
        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
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

        if (empty($data['model']) || $entity->get('model') != $data['model'] || $entity->get('type') != 'anr') {
            throw new \Exception('Entity `id` not found.');
            return false;
        }

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
     * Delete
     *
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        $this->get('table')->delete($id);
    }
}