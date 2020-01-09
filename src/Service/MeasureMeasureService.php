<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

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
        $id = null;
        if ($data['father'] == $data['child']) {
            throw new \Monarc\Core\Exception\Exception("You cannot add yourself as a component", 412);
        }
        $measureEntity = $this->get('measureEntity');
        $measureTable = $this->get('measureTable');
        $measureMeasureTable = $this->get('table');
        $measuresMeasures = $measureMeasureTable->getEntityByFields(['child' => $data['child']['uuid'], 'father' => $data['father']['uuid']]);

        if (!empty($measuresMeasures)) { // the link already exist
            throw new \Monarc\Core\Exception\Exception('This component already exist for this object', 412);
        }

        $father = $measureTable->getEntity($data['father']);
        $child = $measureTable->getEntity($data['child']);
        $father->addLinkedMeasure($child); //we add the link for the two measure
        $id = $measureTable->save($father);

        return $id;
    }

    public function delete($id)
    {
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
