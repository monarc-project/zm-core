<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

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
     * This method is similar to getList except with additional query filters
     * @see #getList
     * @param int $page The page to fetch, starting at 1
     * @param int $limit The maximum number of elements to fetch in one page
     * @param null $order The order
     * @param null $filter The filter fields
     * @param null $asset The asset to filter
     * @param null $category The category to filter
     * @param null $model The model to filter
     * @param null $anr The ANR to filter
     * @param null $lock Whether or not the children categories should be fetched too
     * @return
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $model = null, $anr = null, $lock = null)
    {
        return $this->get('objectService')->getListSpecific($page, $limit, $order, $filter, $asset, $category, $model, $anr, $lock);
    }

    /**
     * Returns the direct parents of the provided object
     * @param int $anrid The ANR id
     * @param int $id The object ID
     * @return mixed The direct parent in the ANR
     * @throws \MonarcCore\Exception\Exception If the entity does not exist
     */
    public function getParents($anrid, $id)
    {
        $object = $this->get('table')->getEntity($id);
        if (!$object) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        //verify object is linked to an anr
        if (!$this->get('table')->checkInAnr($anrid, $id)) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist for this ANR', 412);
        }

        return $this->get('objectObjectTable')->getDirectParentsInAnr($anrid, $id);
    }

    /**
     * Returns the complete object details including dependencies
     * @param int $id The object ID
     * @param string $context The context in which the object is retrieved
     * @param int $anr The ANR ID
     * @return mixed
     */
    public function getCompleteEntity($id, $context = MonarcObject::CONTEXT_BDC, $anr = null)
    {
        return $this->get('objectService')->getCompleteEntity($id, $context, $anr);
    }
}