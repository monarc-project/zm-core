<?php
namespace MonarcCore\Service;

/**
 * Guide Service
 *
 * Class GuideService
 * @package MonarcCore\Service
 */
class GuideService extends AbstractService
{

    protected $types = [
        1 => '__context_risks_management',
        2 => '__summary_assessment_trends_threats',
        3 => '__context_anr',
        4 => '__summary_assets_impacts'
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
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $options = []){

        $guides = parent::getList($page, $limit, $order, $filter);

        foreach($guides as $key => $guide) {
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
    public function getEntity($id){

        $guide = $this->get('table')->get($id);

        $guide['type'] = $this->types[$guide['type']];

        return $guide;
    }
}