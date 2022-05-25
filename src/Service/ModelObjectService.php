<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\MonarcObject;

/**
 * TODO: refactor the class. Model table doesn't have methods are called in the AbstractService.
 */
class ModelObjectService extends AbstractService
{
    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;
    protected $sourceTable;
    protected $modelTable;
    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    ];
    protected $dependencies = ['asset', 'category', 'rolfTag', 'source', 'model'];

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        if (!empty($data['id']) && !empty($data['model'])) {
            $obj = $this->get('table')->getEntity($data['id']);
            if (!$obj->get('model') && $obj->get('type') == 'bdc') {
                $model = $data['model'];
                $obj->setDbAdapter($this->get('table')->getDb());
                $data = $obj->getJsonArray([
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
                ]);
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

        /** @var MonarcObject $monarcObject */
        $monarcObject = $this->get('entity');
        $monarcObject->exchangeArray($data);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($monarcObject, $dependencies);

        $monarcObject->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($monarcObject);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /** @var MonarcObject $monarcObject */
        $monarcObject = $this->get('table')->getEntity($id);

        if (empty($data['model']) || $monarcObject->get('model') != $data['model'] || $monarcObject->get('type') != 'anr') {
            throw new \Monarc\Core\Exception\Exception('Entity `id` not found.');
        }

        $monarcObject->setDbAdapter($this->get('table')->getDb());
        $monarcObject->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Monarc\Core\Exception\Exception('Data missing', 412);
        }
        $monarcObject->exchangeArray($data);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($monarcObject, $dependencies);

        $monarcObject->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $this->get('table')->save($monarcObject);
    }


    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $this->get('table')->delete($id);
    }
}
