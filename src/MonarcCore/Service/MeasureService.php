<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
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
    protected $categoryTable;


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
          $desc = ($order == "-code");
          if(!$desc)
            uasort($data, function($a,$b){
              return strnatcmp ( $a['code'],  $b['code'] );});
          else
            uasort($data, function($a,$b){return strnatcmp ( $b['code'],  $a['code'] );});
        }
        if($limit !=0)
          return array_slice($data, ($page - 1) * $limit, $limit, false);
        else
          return array_slice($data, 0, null, false);
    }

    public function delete($id)
    {
      $category = null;
      if($this->get('table')->getEntity($id)->getCategory() != null) // fetch teh category
        $category = $this->get('table')->getEntity($id)->getCategory()->getId();

      parent::delete($id);
      if($category != null) //check if the measure has a category
      {
        $categoryTable = $this->get('categoryTable');
        $categ = $categoryTable->getEntity($category);

        if(count($categ->measures)==0) //if the category is empty delete it
        {
          $categoryTable->delete($category);
        }
      }
    }

    public function deleteListFromAnr($data, $anrId = null)
    {
      $categoryTable = $this->get('categoryTable');
      $categories = [];
      foreach ($data as $id) {
        if($this->get('table')->getEntity($id)->getCategory() != null)
          array_push($categories, $this->get('table')->getEntity($id)->getCategory()->getId());
      }

      parent::deleteListFromAnr($data, $anrId);

      foreach (array_unique($categories) as $category) {
        $categ = $categoryTable->getEntity($category);
        if(count($categ->measures)==0) //if the category is empty delete it
        {
          $categoryTable->delete($category);
        }
      }
    }
}
