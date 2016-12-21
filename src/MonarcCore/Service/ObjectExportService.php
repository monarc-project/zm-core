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
    protected $categoryTable;
    protected $assetService;

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

    /*
     * IMPORT
     */

    public function importFromArray($data,$anr, $modeImport = 'merge', &$objectsCache = array()){
        if(isset($data['type']) && $data['type'] == 'object' &&
            array_key_exists('version', $data) && $data['version'] == $this->getVersion()){

            if(isset($data['object']['name'.$this->getLanguage()]) && isset($objectsCache[$data['object']['name'.$this->getLanguage()]])){
                return $objectsCache['objects'][$data['object']['name'.$this->getLanguage()]];
            }
            // import asset
            $assetId = $this->get('assetService')->importFromArray($data['asset'],$anr,$objectsCache);

            if($assetId){
                // import categories
                $idCateg = $this->importFromArrayCategories($data['categories'],$data['object']['category'],$anr->get('id'));
                
                /*
                 * INFO:
                 * Selon le mode d'import, la contruction de l'objet ne sera pas la même
                 * Seul un objet SCOPE_GLOBAL (scope) pourra être dupliqué par défaut
                 * Sinon c'est automatiquement un test de fusion, en cas d'échec de fusion on part sur une "duplication" (création)
                 */
                if($data['object']['scope'] == \MonarcCore\Model\Entity\ObjectSuperClass::SCOPE_GLOBAL &&
                    $modeImport == 'duplicate'){
                    // Cela sera traité après le "else"
                }else{ // Fuusion
                    /*
                     * Le pivot pour savoir si on peut faire le merge est:
                     * 1. Même nom
                     * 2. Même catégorie
                     * 3. Même type d'actif
                     * 4. Même scope
                     */
                    $object = current($this->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'name'.$this->getLanguage() => $data['object']['name'.$this->getLanguage()],
                        // il faut que le scope soit le même sinon souci potentiel sur l'impact des valeurs dans les instances (ex : on passe de local à global, toutes les instances choperaient la valeur globale)
                        'scope' => $data['object']['scope'],
                        // il faut bien sûr que le type d'actif soit identique sinon on mergerait des torchons et des serviettes, ça donne des torchettes et c'est pas cool
                        'asset' => $assetId,
                        'category' => $idCateg
                    ]));
                    if(!empty($object)){
                        $object->setDbAdapter($this->get('table')->getDb());
                        $object->setLanguage($this->getLanguage());
                    }
                    // Si il existe, c'est bien, on ne fera pas de "new"
                    // Sinon, on passera dans la création d'un nouvel "object"
                }

                $toExchange = $data['object'];
                if(empty($object)){
                    $class = $this->get('table')->getClass();
                    $object = new $class();
                    $object->setDbAdapter($this->get('table')->getDb());
                    $object->setLanguage($this->getLanguage());
                    // Si on passe ici, c'est qu'on est en mode "duplication", il faut donc vérifier qu'on n'est pas plusieurs fois le même "name"
                    $suffixe = 0;
                    $current = current($this->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'name'.$this->getLanguage() => $toExchange['name'.$this->getLanguage()]
                    ]));
                    while(!empty($current)){
                        $suffixe++;
                        $current = current($this->get('table')->getEntityByFields([
                            'anr' => $anr->get('id'),
                            'name'.$this->getLanguage() => $toExchange['name'.$this->getLanguage()].' - Imp. #'.$suffixe
                        ]));
                    }
                    if($suffixe > 0){ // sinon inutile de modifier le nom, on garde celui de la source
                        for($i=1;$i<=4;$i++){
                            if(!empty($toExchange['name'.$i])){ // on ne modifie que pour les langues renseignées
                                $toExchange['name'.$i] .= ' - Imp. #'.$suffixe;
                            }
                        }
                    }
                }else{
                    // Si l'objet existe déjà, on risque de lui recréer des fils qu'il a déjà, dans ce cas faut détacher tous ses fils avant de lui re-rattacher (après import)
                    $links = $this->get('objectObjectService')->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'father' => $object->get('id')
                    ],['position' => 'DESC']);
                    foreach($links as $l){
                        if(!empty($l)){
                            $this->get('objectObjectService')->get('table')->delete($l->get('id'));
                        }
                    }
                }
                unset($toExchange['id']);
                $toExchange['anr'] = $anr->get('id');
                $toExchange['asset'] = $assetId;
                $toExchange['category'] = $idCateg;
                $object->exchangeArray($toExchange);
                $this->setDependencies($object,['anr', 'category', 'asset']);
                $object->addAnr($anr);
                $idObj = $this->get('table')->save($object);

                $objectsCache['objects'][$data['object']['name'.$this->getLanguage()]] = $idObj;

                //on s'occupe des enfants
                if(!empty($data['children'])){
                    foreach($data['children'] as $c){
                        $child = $this->importFromArray($c, $anr, $modeImport, $objectsCache);

                        if($child){
                            $class = $this->get('objectObjectService')->get('table')->getClass();
                            $oo = new $class();
                            $oo->setDbAdapter($this->get('objectObjectService')->get('table')->getDb());
                            $oo->setLanguage($this->getLanguage());
                            $oo->exchangeArray([
                                'anr' => $anr->get('id'),
                                'father' => $idObj,
                                'child' => $child,
                                'implicitPosition' => 2
                            ]);
                            $this->setDependencies($oo,['father', 'child', 'anr']);
                            $this->get('objectObjectService')->get('table')->save($oo);
                        }
                    }
                }
                return $idObj;
            }
        }
        return false;
    }

    protected function importFromArrayCategories($data,$idCateg,$anrId){
        $return = null;
        if(!empty($data[$idCateg])){
            // On commence par le parent
            $idParent = $this->importFromArrayCategories($data,$data[$idCateg]['parent'],$anrId);

            $categ = current($this->get('categoryTable')->getEntityByFields([
                'anr' => $anrId,
                'parent' => $idParent,
                'label'.$this->getLanguage() => $data[$idCateg]['label'.$this->getLanguage()]
            ]));
            if(empty($categ)){ // on crée une nouvelle catégorie
                $class = $this->get('categoryTable')->getClass();
                $categ = new $class();
                $categ->setDbAdapter($this->get('categoryTable')->getDb());
                $categ->setLanguage($this->getLanguage());

                $toExchange = $data[$idCateg];
                unset($toExchange['id']);
                $toExchange['anr'] = $anrId;
                $toExchange['parent'] = $idParent;
                $toExchange['implicitPosition'] = 2;
                // le "exchangeArray" permet de gérer la position de façon automatique & de mettre à jour le "root"
                $categ->exchangeArray($toExchange);
                $this->setDependencies($categ,['anr','parent']);
                
                $return = $this->get('categoryTable')->save($categ);
            }else{ // sinon on utilise l'éxistant
                $return = $categ->get('id');
            }

        }
        return $return;
    }
}
