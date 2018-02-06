<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

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
        'label',
        'creator'
    ];

    /**
     * @inheritdoc
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
