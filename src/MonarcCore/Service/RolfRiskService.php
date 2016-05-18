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
        'label1', 'label2', 'label3', 'label4',
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
                    $entity->setRolfCategory($key, $rolfCategory);
                }
            }
        }

        $rolfTags = $entity->get('tags');
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
}