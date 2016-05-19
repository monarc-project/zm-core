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

        $rolfCategories = $entity->get('rolfCategories');
        if (!empty($rolfCategories)) {
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if (!empty($rolfCategoryId)) {
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);

                    $entity->setRolfCategory($key, $rolfCategory);
                }
            }
        }

        $rolfTags = $entity->get('rolfTags');
        if (!empty($rolfTags)) {
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if (!empty($rolfTagId)) {
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setRolfTag($key, $rolfTag);
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

        $rolfCategories = isset($data['rolfCategories']) ? $data['rolfCategories'] : array();
        unset($data['rolfCategories']);
        $rolfTags = isset($data['rolfTags']) ? $data['rolfTags'] : array();
        unset($data['rolfTags']);

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);
        $entity->get('rolfCategories')->initialize();
        $entity->get('rolfTags')->initialize();

        foreach($entity->get('rolfCategories') as $rolfCategory){
            if (in_array($rolfCategory->get('id'), $rolfCategories)){
                unset($rolfCategories[array_search($rolfCategory->get('id'), $rolfCategories)]);
            } else {
                $entity->get('rolfCategories')->removeElement($rolfCategories);
            }
        }

        foreach($entity->get('rolfTags') as $rolfTag){
            if (in_array($rolfTag->get('id'), $rolfTags)){
                unset($rolfTags[array_search($rolfTag->get('id'), $rolfTags)]);
            } else {
                $entity->get('rolfTags')->removeElement($rolfTags);
            }
        }

        if (!empty($rolfCategories)){
            $rolfCategoryTable = $this->get('rolfCategoryTable');
            foreach ($rolfCategories as $key => $rolfCategoryId) {
                if(!empty($rolfCategoryId)){
                    $rolfCategory = $rolfCategoryTable->getEntity($rolfCategoryId);
                    $entity->setRolfCategory($key, $rolfCategory);
                }
            }
        }

        if (!empty($rolfTags)){
            $rolfTagTable = $this->get('rolfTagTable');
            foreach ($rolfTags as $key => $rolfTagId) {
                if(!empty($rolfTagId)){
                    $rolfTag = $rolfTagTable->getEntity($rolfTagId);
                    $entity->setRolfTag($key, $rolfTag);
                }
            }
        }

        return $this->get('table')->save($entity);
    }
}