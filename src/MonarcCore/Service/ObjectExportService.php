<?php
namespace MonarcCore\Service;

/**
 * Object Service Export
 *
 * Class ObjectExportService
 * @package MonarcCore\Service
 */
class ObjectExportService extends AbstractService
{
	protected $assetExportService;
	protected $objectObjectService;

    public function generateExportArray($id, &$filename = ""){
        if (empty($id)) {
            throw new \Exception('Object to export is required',412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $objectObj = array(
            'id' => 'id',
            'mode' => 'mode',
            'scope' => 'scope',
            'name1' => 'name1',
            'name2' => 'name2',
            'name3' => 'name3',
            'name4' => 'name4',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'disponibility' => 'disponibility',
        );
        $return = array(
            'type' => 'object',
            'object' => $entity->getJsonArray($objectObj),
            'version' => $this->getVersion(),
        );
        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('name'.$this->getLanguage()));

        // Récupération catégories
        $categ = $entity->get('category');
        if(!empty($categ)){
            $categObj = array(
                'id' => 'id',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
            );

            while(!empty($categ)){
                $categFormat = $categ->getJsonArray($categObj);
                if(empty($return['object']['category'])){
                    $return['object']['category'] = $categFormat['id'];
                }
                $return['categories'][$categFormat['id']] = $categFormat;
                $return['categories'][$categFormat['id']]['parent'] = null;

                $parent = $categ->get('parent');
                if(!empty($parent)){
                    $parentForm = $categ->get('parent')->getJsonArray(array('id'=>'id'));
                    $return['categories'][$categFormat['id']]['parent'] = $parentForm['id'];
                    $categ = $parent;
                }else{
                    $categ = null;
                }
            }
        }else{
            $return['object']['category'] = null;
            $return['categories'] = null;
        }

        // Récupération asset
        $asset = $entity->get('asset');
        $return['asset'] = null;
        $return['object']['asset'] = null;
        if(!empty($asset)){
            $asset = $asset->getJsonArray(array('id'));
            $return['object']['asset'] = $asset['id'];
            $return['asset'] = $this->get('assetExportService')->generateExportArray($asset['id']);
        }

        // Récupération children(s)
        $children = $this->get('objectObjectService')->getChildren($entity->get('id'));
        $return['children'] = null;
        if(!empty($children)){
            $return['children'] = array();
            foreach ($children as $child) {
                $return['children'][$child->get('child')->get('id')] = $this->generateExportArray($child->get('child')->get('id'));
            }
        }

        return $return;
    }
}
