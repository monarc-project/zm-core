<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Table\MeasureMeasureTable;

/**
 * Measure Service
 *
 * Class MeasureService
 * @package Monarc\Core\Service
 */
class MeasureMeasureService extends AbstractService
{
    protected $dependencies = [];
    protected $filterColumns = [];
    protected $forbiddenFields = ['anr'];
    protected $measureTable;
    protected $measureEntity;

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        if ($data['father'] === $data['child']) {
            throw new Exception('You cannot add yourself as a component', 412);
        }
        $measureTable = $this->get('measureTable');
        $measureMeasureTable = $this->get('table');
        $measuresMeasures = $measureMeasureTable->getEntityByFields(
            ['child' => $data['child'], 'father' => $data['father']]
        );

        if (!empty($measuresMeasures)) { // the link already exist
            throw new Exception('This component already exist for this object', 412);
        }

        $father = $measureTable->getEntity($data['father']);
        $child = $measureTable->getEntity($data['child']);
        $father->addLinkedMeasure($child); //we add the link for the two measure

        return $measureTable->save($father);
    }

    public function delete($id)
    {
        /** @var MeasureMeasureTable $measureTable */
        $measureTable = $this->get('measureTable');
        $father = $measureTable->getEntity($id['father']);
        $child = $measureTable->getEntity($id['child']);
        $father->deleteLinkedMeasure($child);
        $measureTable->save($father);
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

        return \array_slice($data, ($page - 1) * $limit, $limit);
    }
}
