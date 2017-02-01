<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Table\GuideTable;

/**
 * Guide Service
 *
 * Class GuideService
 * @package MonarcCore\Service
 */
class GuideService extends AbstractService
{
    protected $types = [
        1 => 'Risk analysis context',
        2 => 'Risk management context',
        3 => 'Summary assessment of trends and threats',
        4 => 'Summary of assets / impacts'
    ];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param array $options
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $options = [])
    {
        $guides = parent::getList($page, $limit, $order, $filter);

        foreach ($guides as $key => $guide) {
            $guides[$key]['type_id'] = $guides[$key]['type'];
            $guides[$key]['type'] = $this->types[$guide['type']];
        }

        return $guides;
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id)
    {
        $guide = $this->get('table')->get($id);

        $guide['type'] = $this->types[$guide['type']];

        return $guide;
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true)
    {
        /** @var GuideTable $table */
        $table = $this->get('table');
        $currentGuide = $table->getEntityByFields(['type' => $data['type']]);

        if (count($currentGuide)) {
            throw new \Exception('Only one guide by category', 412);
        }

        return parent::create($data, $last);
    }
}