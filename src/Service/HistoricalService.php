<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Historical Service
 *
 * Class HistoricalService
 * @package Monarc\Core\Service
 */
class HistoricalService extends AbstractService
{
    protected $filterColumns = [
        'type', 'action',
        'label1', 'label2', 'label3', 'label4',
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
