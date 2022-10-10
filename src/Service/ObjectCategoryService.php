<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AnrObjectCategoryTable;
use Monarc\Core\Model\Table\ModelTable;
use Monarc\Core\Model\Table\MonarcObjectTable;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package Monarc\Core\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $anrObjectCategoryTable;
    protected $monarcObjectTable;
    protected $anrTable;//required for autopositionning of anrobjectcategories
    protected $userAnrTable;
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['root', 'parent', 'anr'];//required for autopositionning

    protected ModelTable $modelTable;

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
    public function getListSpecific(
        $page = 1,
        $limit = 25,
        $order = null,
        $filter = null,
        $filterAnd = [],
        $modelId = null
    ) {
        $objectCategories = $this->getList($page, $limit, $order, $filter, $filterAnd);
        $result = $objectCategories;

        $model = null;
        if ($modelId !== null) {
            $model = $this->modelTable->findById((int)$modelId);
        }

        $currentObjectCategoriesListId = [];
        foreach ($objectCategories as $key => $objectCategory) {
            if (\is_object($result[$key]['objects'])) {
                $result[$key]['objects'] = [];
            }
            if ($model !== null) {
                /** @var ObjectSuperClass $object */
                foreach ($objectCategory['objects'] as $object) {
                    $result[$key]['objects'][] = [
                        'uuid' => $object->getUuid(),
                        'name1' => $object->getName(1),
                        'name2' => $object->getName(2),
                        'name3' => $object->getName(3),
                        'name4' => $object->getName(4),
                        'isLinkedToAnr' => $object->isLinkedToAnr($model->getAnr()),
                    ];
                }
            }
            $currentObjectCategoriesListId[] = $objectCategory['id'];
        }

        //retrieve parent
        if (empty($filterAnd['id'])) {
            foreach ($objectCategories as $objectCategory) {
                $this->addParent($result, $objectCategory, $currentObjectCategoriesListId);
            }
        }

        return $result;
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
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->get('entity');

        $objectCategory->exchangeArray($data);

        $this->setDependencies($objectCategory, $this->dependencies);

        $objectCategory->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $this->get('table')->save($objectCategory);

        return $objectCategory->getJsonArray();
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /** @var ObjectCategorySuperClass $objectCategory */
        $objectCategory = $this->get('table')->getEntity($id);
        $objectCategory->setLanguage($this->getLanguage());
        $objectCategory->setDbAdapter($this->table->getDb());

        $isRootCategoryBeforeUpdated = $objectCategory->isCategoryRoot();
        $previousRootCategory = $objectCategory->getRoot();

        $objectCategory->exchangeArray($data);

        $this->setDependencies($objectCategory, $this->dependencies);

        $objectCategory->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $this->get('table')->save($objectCategory);

        // Perform operations to link/unlink the category and its root one to/from Anr.
        if ($isRootCategoryBeforeUpdated && !$objectCategory->isCategoryRoot()) {
            $this->unlinkCategoryFromAnr($objectCategory);
            $this->linkCategoryToAnr($objectCategory->getRoot());
        } elseif (!$isRootCategoryBeforeUpdated && $objectCategory->isCategoryRoot()) {
            $this->linkCategoryToAnr($objectCategory);
            /** @var MonarcObjectTable $monarcObjectTable */
            $monarcObjectTable = $this->get('monarcObjectTable');
            if ($previousRootCategory !== null
                && !$monarcObjectTable->hasObjectsUnderRootCategoryExcludeObject($previousRootCategory)
            ) {
                $this->unlinkCategoryFromAnr($previousRootCategory);
            }
        }

        return $objectCategory->getJsonArray();
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
        foreach ($children as $c) {
            $this->delete($c->getId());
        }

        $this->get('monarcObjectTable')->getRepository()->createQueryBuilder('t')
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

        /** @var ObjectCategorySuperClass $anrObjectCategory */
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

    protected function unlinkCategoryFromAnr(ObjectCategorySuperClass $objectCategory): void
    {
        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');

        $anrObjectCategories = $anrObjectCategoryTable->findByObjectCategory($objectCategory);
        foreach ($anrObjectCategories as $anrObjectCategory) {
            $anrObjectCategoryTable->delete($anrObjectCategory->getId());
        }
    }

    /**
     * We need to link every Anr of Objects which are under the root category or it's children.
     */
    protected function linkCategoryToAnr(ObjectCategorySuperClass $objectCategory): void
    {
        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('monarcObjectTable');
        $objects = $monarcObjectTable->getObjectsUnderRootCategory($objectCategory);

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');

        foreach ($objects as $object) {
            foreach ($object->getAnrs() as $anr) {
                if (isset($anrs[$anr->getId()])
                    || $anrObjectCategoryTable->findOneByAnrAndObjectCategory($anr, $objectCategory) !== null
                ) {
                    continue;
                }

                $anrObjectCategory = new AnrObjectCategory();
                $anrObjectCategory->setAnr($anr)->setCategory($objectCategory);
                $anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());
                $anrObjectCategory->exchangeArray(['implicitPosition' => 2]);

                $anrObjectCategoryTable->save($anrObjectCategory);

                $anrs[$anr->getId()] = true;
            }
        }
    }
}
