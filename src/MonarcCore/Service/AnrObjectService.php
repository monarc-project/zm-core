<?php
namespace MonarcCore\Service;

/**
 * Anr Object Service
 *
 * Class AnrObjectService
 * @package MonarcCore\Service
 */
class AnrObjectService extends AbstractService
{
    protected $objectObjectTable;
    protected $objectService;
    protected $userAnrTable;

    /**
     * Get List Specific
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $asset
     * @param null $category
     * @param null $model
     * @param null $anr
     * @param null $lock
     * @return mixed
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $model = null, $anr = null, $lock = null)
    {
        return $this->get('objectService')->getListSpecific($page, $limit, $order, $filter, $asset, $category, $model, $anr, $lock);
    }

    /**
     * Get Parents
     *
     * @param $anrid
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function getParents($anrid, $id)
    {
        $object = $this->get('table')->getEntity($id);
        if (!$object) {
            throw new \Exception('Entity does not exist', 412);
        }

        //verify object is linked to an anr
        if (!$this->get('table')->checkInAnr($anrid, $id)) {
            throw new \Exception('Entity does not exist for this ANR', 412);
        }

        return $this->get('objectObjectTable')->getDirectParentsInAnr($anrid, $id);
    }

    /**
     * @param $id
     * @param string $context
     * @param integer $anr
     * @return mixed
     */
    public function getCompleteEntity($id, $context = Object::CONTEXT_BDC, $anr = null)
    {
        return $this->get('objectService')->getCompleteEntity($id, $context, $anr);
    }
}