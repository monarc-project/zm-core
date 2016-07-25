<?php
namespace MonarcCore\Service;

/**
 * DocModels Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class DocModelService extends AbstractService
{
    protected $filterColumns = array(
    	//'category',
        //'description',
    );

    /**
     * Get DocModel
     *
     * @param $id
     * @return array
     */
    public function getEntity($id)
    {
    	$doc = $this->get('table')->getEntity($id);
        $name = pathinfo($doc->get('path'),PATHINFO_BASENAME);
        $name = explode('_',$name);
        unset($name[0]);
        $name = implode('_',$name);
        return array('name'=>$name,'path'=>$doc->get('path'),'content'=>file_get_contents($doc->get('path')));
    }
}