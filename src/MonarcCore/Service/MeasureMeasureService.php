<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Measure Service
 *
 * Class MeasureService
 * @package MonarcCore\Service
 */
class MeasureMeasureService extends AbstractService
{
    protected $dependencies = [];
    protected $filterColumns = [];
    protected $forbiddenFields = ['anr'];

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        // Filter unwanted fields
        $this->filterPatchFields($data);
        parent::patch($id, $data);
    }

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $data = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            0,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        return array_slice($data, ($page - 1) * $limit, $limit, false);
    }
}
