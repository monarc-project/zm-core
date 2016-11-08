<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Table\AnrTable;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{
    protected $anrTable;
    protected $modelTable;
    protected $amvService;
    protected $modelService;
    protected $objectTable;
    protected $assetExportService;

    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];

    protected $dependencies = ['anr', 'model[s]()'];
    protected $forbiddenFields = ['anr'];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true) {

        $entity = $this->get('entity');
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Exception('This risk analysis does not exist', 412);
            }
            $entity->setAnr($anr);
        }
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $entity->status = 1;

        return $this->get('table')->save($entity);
    }


    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id,$data){

        $this->filterPatchFields($data);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (($entity->mode == Asset::MODE_SPECIFIC) && ($data['mode'] == Asset::MODE_GENERIC)) {
            if (isset($data['models'])) {
                //delete specific model
                /** @var ModelService $modelService */
                $modelService = $this->get('modelService');
                $modelService->unsetSpecificModels($data);
            }
        }

        $models = isset($data['models']) ? $data['models'] : array();
        $follow = isset($data['follow']) ? $data['follow'] : null;
        unset($data['models']);
        unset($data['follow']);

        $entity->exchangeArray($data);
        if ($entity->get('models')) {
            $entity->get('models')->initialize();
        }

        /** @var AmvService $amvService */
        $amvService  = $this->get('amvService');
        if (!$amvService->checkAMVIntegrityLevel($models, $entity, null, null, $follow)) {
            throw new \Exception('Integrity AMV links violation', 412);
        }

        if ($entity->mode == Asset::MODE_SPECIFIC) {
            $associateObjects = $this->get('objectTable')->getGenericByAssetId($entity->getId());
            if (count($associateObjects)) {
                throw new \Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($entity, $models)) {
            throw new \Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        foreach($entity->get('models') as $model){
            if (in_array($model->get('id'), $models)){
                unset($models[array_search($model->get('id'), $models)]);
            } else {
                $entity->get('models')->removeElement($model);
            }
        }

        if (!empty($models)){
            $modelTable = $this->get('modelTable');
            foreach ($models as $key => $modelId) {
                if(!empty($modelId)){
                    $model = $modelTable->getEntity($modelId);
                    $entity->setModel($key, $model);
                }
            }

            if ($follow) {
                $amvService->enforceAMVtoFollow($models, null, null, $entity);
            }
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }

    /**
     * Export Asset
     *
     * @param $id
     * @return array
     */
    public function exportAsset(&$data){
        if (empty($data['id'])) {
            throw new \Exception('Asset to export is required',412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }
        $filename = "";
        $return = $this->get('assetExportService')->generateExportArray($data['id'],$filename);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return),$data['password']));
    }

    public function generateExportArray($id, &$filename = ""){
        if (empty($id)) {
            throw new \Exception('Asset to export is required',412);
        }

        $entity = $this->get('table')->getEntity($id);
        if (empty($entity)) {
            throw new \Exception('Asset not found',412);
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('code'));

        $assetObj = array(
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
            'mode' => 'mode',
            'type' => 'type',
            'code' => 'code',
        );
        $return = array(
            'type' => 'asset',
            'asset' => $entity->getJsonArray($assetObj),
            'version' => $this->getVersion(),
        );
        $amvService = $this->get('amvService');
        $amvTable = $amvService->get('table');

        $amvResults = $amvTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.asset = :asset")
            ->setParameter(':asset',$entity->get('id'));
        $anrId = $entity->get('anr');
        if(empty($anrId)){
            $amvResults = $amvResults->andWhere('t.anr IS NULL');
        }else{
            $anrId = $anrId->get('id');
            $amvResults = $amvResults->andWhere('t.anr = :anr')->setParameter(':anr',$anrId);
        }
        $amvResults = $amvResults->getQuery()->getResult();

        $data_amvs = $data_threats = $data_vuls = $data_themes = $t_ids = $v_ids = $m_ids = $tt_ids = $threats = $vuls = $themes = array();

        $amvObj = array(
            'id' => 'v',
            'threat' => 'o',
            'vulnerability' => 'o',
            'measure1' => 'o',
            'measure2' => 'o',
            'measure3' => 'o',
            'status' => 'v',
        );
        $treatsObj = array(
            'id' => 'id',
            'theme' => 'theme',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'c' => 'c',
            'i' => 'i',
            'd' => 'd',
            'status' => 'status',
            'isAccidental' => 'isAccidental',
            'isDeliberate' => 'isDeliberate',
            'descAccidental1' => 'descAccidental1',
            'descAccidental2' => 'descAccidental2',
            'descAccidental3' => 'descAccidental3',
            'descAccidental4' => 'descAccidental4',
            'exAccidental1' => 'exAccidental1',
            'exAccidental2' => 'exAccidental2',
            'exAccidental3' => 'exAccidental3',
            'exAccidental4' => 'exAccidental4',
            'descDeliberate1' => 'descDeliberate1',
            'descDeliberate2' => 'descDeliberate2',
            'descDeliberate3' => 'descDeliberate3',
            'descDeliberate4' => 'descDeliberate4',
            'exDeliberate1' => 'exDeliberate1',
            'exDeliberate2' => 'exDeliberate2',
            'exDeliberate3' => 'exDeliberate3',
            'exDeliberate4' => 'exDeliberate4',
            'typeConsequences1' => 'typeConsequences1',
            'typeConsequences2' => 'typeConsequences2',
            'typeConsequences3' => 'typeConsequences3',
            'typeConsequences4' => 'typeConsequences4',
            'trend' => 'trend',
            'comment' => 'comment',
            'qualification' => 'qualification',
        );
        $vulsObj = array(
            'id' => 'id',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        );
        $themesObj = array(
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        );

        foreach($amvResults as $amv){
            $t_ids[$amv->get('threat')->get('id')] = $amv->get('threat')->get('id');
            $v_ids[$amv->get('vulnerability')->get('id')] = $amv->get('vulnerability')->get('id');

            for($i = 1; $i <= 3; $i++){
                $measure = $amv->get('measure'.$i);
                if(!empty($measure)){
                    $m_ids[$measure->get('id')] = $measure->get('id');    
                }
            }

            $data_amvs[$amv->get('id')] = array();
            foreach($amvObj as $k => $v){
                switch($v){
                    case 'v':
                        $data_amvs[$amv->get('id')][$k] = $amv->get($k);
                        break;
                    case 'o':
                        $o = $amv->get($k);
                        if(empty($o)){
                            $data_amvs[$amv->get('id')][$k] = null;
                        }else{
                            $o = $amv->get($k)->getJsonArray();
                            $data_amvs[$amv->get('id')][$k] = $o['id'];

                            if($k == 'threat'){
                                $return['threats'][$o['id']] = $amv->get($k)->getJsonArray($treatsObj);
                                if(!empty($return['threats'][$o['id']]['theme'])){
                                    $return['threats'][$o['id']]['theme'] = $return['threats'][$o['id']]['theme']->getJsonArray($themesObj);

                                    $return['themes'][$return['threats'][$o['id']]['theme']['id']] = $return['threats'][$o['id']]['theme'];

                                    $return['threats'][$o['id']]['theme'] = $return['threats'][$o['id']]['theme']['id'];
                                }
                            }elseif($k == 'vulnerability'){
                                $return['vuls'][$o['id']] = $amv->get($k)->getJsonArray($vulsObj);
                            }
                        }
                        break;
                }
            }
        }

        $return['amvs'] = $data_amvs;
        return $return;
    }
}
