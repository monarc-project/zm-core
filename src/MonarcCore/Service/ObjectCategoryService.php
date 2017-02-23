<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
    protected $objectTable;
    protected $rootTable;//required for autopositionning
    protected $parentTable;//required for autopositionning
    protected $anrTable;//required for autopositionning of anrobjectcategories
    protected $userAnrTable;
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['root', 'parent', 'anr'];//required for autopositionning

    /**
     * Get Entity
     *
     * @param $id
     * @return array
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
     * Get List Specific
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param array $filterAnd
     * @return mixed
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
     * Add parent
     *
     * @param $objects
     * @param $object
     * @param $currentObjectsListId
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
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
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
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
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
     * Delete
     *
     * @param $id
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

        $this->get('objectTable')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.category', ':categ')
            ->setParameter(':categ', null)
            ->where('t.category = :c')
            ->setParameter(':c', $id)
            ->getQuery()->getResult();

        $this->get('table')->delete($id);
    }

    /**
     * Patch Library Category
     *
     * @param $categoryId
     * @param $data
     * @return mixed|null
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