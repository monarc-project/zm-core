<?php
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

    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    protected $dependencies = ['root', 'parent'];//required for autopositionning

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){
        $entity = $this->get('table')->get($id);

        $entity['previous'] = null;
        if($entity['position'] == 1){
            $entity['implicitPosition'] = 1;
        }else{
            $pos = $this->get('table')->getRepository()->createQueryBuilder('t')->select('count(t.id)');
            if(empty($entity['parent'])){
                $pos = $pos->where('t.parent IS NULL');
            }else{
                $pos = $pos->where('t.parent = :parent')
                    ->setParameter(':parent', $entity['parent']->id);
            }

            $pos = $pos->getQuery()->getSingleScalarResult();
            if($entity['position'] >= $pos){
                $entity['implicitPosition'] = 2;
            }else{
                $entity['implicitPosition'] = 3;
                // Autre chose ?te
                $prev = $this->get('table')->getRepository()->createQueryBuilder('t')->select('t.id');
                if(empty($entity['parent'])){
                    $prev = $prev->where('t.parent IS NULL');
                }else{
                    $prev = $prev->where('t.parent = :parent')
                        ->setParameter(':parent', $entity['parent']->id);
                }
                $prev = $prev->andWhere('t.position = :pos')
                    ->setParameter(':pos',$entity['position']-1)
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
     * @param int $parentId
     * @return mixed
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $parentId = 0){
        if ($parentId <= 0) {
            $objects = $this->getList($page, $limit, $order, $filter);
        } else {
            $filterAnd = ['parent' => $parentId];

            $objects = $this->get('table')->fetchAllFiltered(
                array_keys($this->get('entity')->getJsonArray()),
                $page,
                $limit,
                $this->parseFrontendOrder($order),
                $this->parseFrontendFilter($filter, $this->filterColumns),
                $filterAnd
            );
        }


        $currentObjectsListId = [];
        foreach($objects as $object) {
            $currentObjectsListId[] = $object['id'];
        }

        //retrieve parent
        foreach($objects as $object) {
            $this->addParent($objects, $object, $currentObjectsListId);
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
    protected function addParent(&$objects, $object, &$currentObjectsListId) {
        if ($object['parent']) {
            if (!in_array($object['parent']->id, $currentObjectsListId)) {

                $parent = $object['parent']->getJsonArray();
                unset($parent['__initializer__']);
                unset($parent['__cloner__']);
                unset($parent['__isInitialized__']);

                $objects[] = $parent;

                $currentObjectsListId[] = $object['parent']->id;

                $this->addParent($objects, $parent, $currentObjectsListId);
            }
        }
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true) {

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
    public function update($id, $data){

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
    public function delete($id) {

        $entity = $this->get('table')->getEntity($id);

        // On supprime en cascade les fils
        $children = $this->get('table')->getRepository()->createQueryBuilder('t')
            ->where('t.parent = :parent')
            ->setParameter(':parent',$id)
            ->getQuery()->getResult();
        foreach($children as $c){
            $this->delete($c->id);
        }

        $this->get('objectTable')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.category', ':categ')
            ->setParameter(':categ',null)
            ->where('t.category = :c')
            ->setParameter(':c',$id)
            ->getQuery()->getResult();

        $this->get('table')->delete($id);
    }

    public function patchLibraryCategory($categoryId, $data) {

        $anrId = $data['anr'];

        if (!isset($data['position'])) {
            throw new \Exception('Position missing', 412);
        }

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategory = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $categoryId])[0];

        if ($data['position'] != $anrObjectCategory->position) {

            $previousAnrObjectCategoryPosition = ($data['position'] > $anrObjectCategory->position) ? $data['position'] : $data['position'] - 1;
            $previousAnrObjectCategory = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'position' => $previousAnrObjectCategoryPosition]);
            if ($previousAnrObjectCategory) {
                $data['implicitPosition'] = 3;
                $data['previous'] = $previousAnrObjectCategory[0];
            } else {
                $data['implicitPosition'] = 1;
                $data['previous'] = null;
            }

            $anrObjectCategory->exchangeArray($data);

            return $this->get('table')->save($anrObjectCategory);
        }
    }
}
