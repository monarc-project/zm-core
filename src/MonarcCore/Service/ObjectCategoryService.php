<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Table\AnrObjectCategoryTable;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package MonarcCore\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $anrObjectCategoryTable;
    protected $MonarcObjectTable;
    protected $rootTable;//required for autopositionning
    protected $parentTable;//required for autopositionning
    protected $anrTable;//required for autopositionning of anrobjectcategories
    protected $userAnrTable;
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['root', 'parent', 'anr'];//required for autopositionning

    /**
     * @inheritdoc
     */
    public function getEntity($id)
    {
        $entity = $this->get('table')->get($id);

        $entity['previous'] = null;
        if ($entity['position'] == 1) {
            $entity['implicitPosition'] = 1;
        } else {
            $pos = $this->get('table')->getRepository()->createQueryBuilder('t')->select('count(t.id)');
            if (empty($entity['parent'])) {
                $pos = $pos->where('t.parent IS NULL');
            } else {
                $pos = $pos->where('t.parent = :parent')
                    ->setParameter(':parent', $entity['parent']->id);
            }

            if ($entity['anr']) {
                $pos->andWhere('t.anr = :anr')->setParameter(':anr', $entity['anr']->id);
            }

            $pos = $pos->getQuery()->getSingleScalarResult();
            if ($entity['position'] >= $pos) {
                $entity['implicitPosition'] = 2;
            } else {
                $entity['implicitPosition'] = 3;
                // Autre chose ?te
                $prev = $this->get('table')->getRepository()->createQueryBuilder('t')->select('t.id');
                if (empty($entity['parent'])) {
                    $prev = $prev->where('t.parent IS NULL');
                } else {
                    $prev = $prev->where('t.parent = :parent')
                        ->setParameter(':parent', $entity['parent']->id);
                }
                if ($entity['anr']) {
                    $prev->andWhere('t.anr = :anr')->setParameter(':anr', $entity['anr']->id);
                }
                $prev = $prev->andWhere('t.position = :pos')
                    ->setParameter(':pos', $entity['position'] - 1)
                    ->getQuery()->getSingleScalarResult();
                $entity['previous'] = $prev;
            }
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = [])
    {
        $objects = $this->getList($page, $limit, $order, $filter, $filterAnd);

        $currentObjectsListId = [];
        foreach ($objects as $object) {
            $currentObjectsListId[] = $object['id'];
        }

        //retrieve parent
        if (empty($filterAnd['id'])) {
            foreach ($objects as $object) {
                $this->addParent($objects, $object, $currentObjectsListId);
            }
        }

        return $objects;
    }

    /**
     * Adds a new parent to this object category
     * @param array $objects Objects
     * @param array $object Object to add
     * @param array $currentObjectsListId Current object cache list
     */
    protected function addParent(&$objects, $object, &$currentObjectsListId)
    {
        if ($object['parent'] && !in_array($object['parent']->id, $currentObjectsListId)) {
            $parent = $object['parent']->getJsonArray();
            unset($parent['__initializer__']);
            unset($parent['__cloner__']);
            unset($parent['__isInitialized__']);

            $objects[] = $parent;

            $currentObjectsListId[] = $object['parent']->id;

            $this->addParent($objects, $parent, $currentObjectsListId);
        }
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $entity = $this->get('entity');
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->table->getDb());
        $entity->exchangeArray($data);

        $this->setDependencies($entity, $this->dependencies);

        $this->get('table')->save($entity);
        return $entity->getJsonArray();

    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->table->getDb());
        $entity->exchangeArray($data);

        $this->setDependencies($entity, $this->dependencies);


        $this->get('table')->save($entity);
        return $entity->getJsonArray();
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        // On supprime en cascade les fils
        $children = $this->get('table')->getRepository()->createQueryBuilder('t')
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()->getResult();
        $i = 1;
        $nbChildren = count($children);
        foreach ($children as $c) {
            $this->delete($c->id, ($i == $nbChildren));
            $i++;
        }

        $this->get('MonarcObjectTable')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.category', ':categ')
            ->setParameter(':categ', null)
            ->where('t.category = :c')
            ->setParameter(':c', $id)
            ->getQuery()->getResult();

        $this->get('table')->delete($id);
    }

    /**
     * Patches the Library Category
     * @param int $categoryId The category ID to patch
     * @param array $data The new data
     * @return mixed|null The resulting object
     */
    public function patchLibraryCategory($categoryId, $data)
    {
        $anrId = $data['anr'];

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategory = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $categoryId])[0];
        $anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());

        //Specific handle of previous data
        if (isset($data['previous'])) {//we get a position but we need an id
            $id = $anrObjectCategoryTable->getRepository()->createQueryBuilder('t')
                ->select('t.id')
                ->where('t.anr = :anrid')
                ->andWhere('t.position = :pos')
                ->setParameters([':anrid' => $anrId, ':pos' => $data['previous']])
                ->getQuery()->getSingleScalarResult();

            $data['previous'] = $id ? $id : null;
        }

        $anrObjectCategory->exchangeArray($data);
        $this->setDependencies($anrObjectCategory, ['anr']);
        return $anrObjectCategoryTable->save($anrObjectCategory);
    }
}
