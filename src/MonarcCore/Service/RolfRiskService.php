<?php
namespace MonarcCore\Service;

/**
 * Rolf Risk Service
 *
 * Class RolfRiskService
 * @package MonarcCore\Service
 */
class RolfRiskService extends AbstractService
{
    protected $rolfCategoryTable;
    protected $rolfTagTable;

    protected $filterColumns = array(
        'code', 'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4'
    );

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $rolfCategories = $entity->get('categories');
        if (!empty($rolfCategories)) {
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if (!empty($rolfCategoryId)) {
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);

                    $entity->setCategory($key, $rolfCategory);
                }
            }
        }

        $rolfTags = $entity->get('tags');
        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setTag($key, $rolfTag);
                }
            }
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){

        $rolfCategories = isset($data['categories']) ? $data['categories'] : array();
        unset($data['categories']);
        $rolfTags = isset($data['tags']) ? $data['tags'] : array();
        unset($data['tags']);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->exchangeArray($data);
        $entity->get('categories')->initialize();
        $entity->get('tags')->initialize();

        foreach($entity->get('categories') as $rolfCategory){
            if (in_array($rolfCategory->get('id'), $rolfCategories)){
                unset($rolfCategories[array_search($rolfCategory->get('id'), $rolfCategories)]);
            } else {
                $entity->get('categories')->removeElement($rolfCategory);
            }
        }

        foreach($entity->get('tags') as $rolfTag){
            if (in_array($rolfTag->get('id'), $rolfTags)){
                unset($rolfTags[array_search($rolfTag->get('id'), $rolfTags)]);
            } else {
                $entity->get('tags')->removeElement($rolfTag);
            }
        }

        if (!empty($rolfCategories)){
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if(!empty($rolfCategoryId)){
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);
                    $entity->setCategory($key, $rolfCategory);
                }
            }
        }

        if (!empty($rolfTags)){
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if(!empty($rolfTagId)){
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setTag($key, $rolfTag);
                }
            }
        }

        return $this->get('table')->save($entity);
    }
}