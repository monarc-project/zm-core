<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Referential Service
 *
 * Class ReferentialService
 * @package Monarc\Core\Service
 */
class ReferentialService extends AbstractService
{
    // TODO: avoid the deps setting up in abstract
    protected $dependencies = ['anr', 'amvs'];
    protected $filterColumns = ['uuid', 'label1', 'label2', 'label3', 'label4'];
    protected $forbiddenFields = ['anr'];

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null)
    {
        $data = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            1,
            0,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        return array_slice($data, ($page - 1) * $limit, $limit, false);
    }
}
