<?php
namespace MonarcCore\Service;

/**
 * Historical Service
 *
 * Class HistoricalService
 * @package MonarcCore\Service
 */
class HistoricalService extends AbstractService
{
    protected $filterColumns = [
        'type', 'action',
        'label1', 'label2', 'label3', 'label4',
        'creator'
    ];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $list = parent::getList($page, $limit, $order, $filter, $filterAnd);
        foreach ($list as $k => $v) {
            if (empty($list[$k]['createdAt'])) {
                $list[$k]['createdAt'] = '';
            } else {
                $list[$k]['createdAt'] = $list[$k]['createdAt']->format('d/m/Y H:i:s');
            }
            $list[$k]['details'] = explode(' / ', $list[$k]['details']);
        }
        return $list;
    }
}