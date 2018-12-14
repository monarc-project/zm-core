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
class MeasureService extends AbstractService
{
    protected $dependencies = ['anr','category', 'amvs', 'referential', 'measuresLinked'];
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4', 'code', 'status'];
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
       list($filterJoin,$filterLeft,$filtersCol) = $this->get('entity')->getFiltersForService();
       $data =  $this->get('table')->fetchAllFiltered(
           array_keys($this->get('entity')->getJsonArray()),
           1,
           0,
           $this->parseFrontendOrder($order),
           $this->parseFrontendFilter($filter, $filtersCol),
           $filterAnd,
           $filterJoin,
           $filterLeft
       );

        // TODO: try to order in SQL instead of php with usort
        if ($order == "code" || $order == "-code") {
          file_put_contents('php://stderr', print_r('coucou je passe ici', TRUE).PHP_EOL);
          $desc = ($order == "-code");
          if(!$desc)
            uasort($data, function($a,$b){
              return strnatcmp ( $a['code'],  $b['code'] );});
          else
            uasort($data, function($a,$b){return strnatcmp ( $b['code'],  $a['code'] );});
        }

        return array_slice($data, ($page - 1) * $limit, $limit, false);
    }
}
