<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $dependencies = ['anr'];
    protected $anrService;
    protected $anrTable;
    protected $objectTable;
    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {
        $entity = $this->get('entity');
        $entity->setLanguage($this->getLanguage());

        //anr
        $dataAnr = [
            'label1' => 'ANR',
            'label2' => 'ANR',
            'label3' => 'ANR',
            'label4' => 'ANR',
        ];
        $anrId = $this->get('anrService')->create($dataAnr);

        $data['anr'] = $anrId;

        //model
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        // If we reached here, our object is ready to be saved.
        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Can accept object
     *
     * @param $modelId
     * @param $object
     * @param $context
     * @throws \Exception
     */
    public function canAcceptObject($modelId, $object, $context)
    {

        //retrieve data
        $data = $this->getEntity($modelId);

        //retrieve object model
        $model = $this->get('entity');
        $model->setDbAdapter($this->get('table')->getDb());
        $model->setLanguage($this->getLanguage());
        $model->exchangeArray($data);

        $asset = $object->asset;

        $authorized = false;

        if ($model->isGeneric) {
            if ($object->mode == self::IS_GENERIC) {
                $authorized = true;
            }
        } else {
            if ($model->isRegulator) { //model is specific and regulated
                if ($asset->mode == self::IS_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                }
            } else { //can receive generic or specifi to himself
                if ($asset->mode == self::IS_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                } else {
                    if ($object->mode == self::IS_SPECIFIC) { //aïe, l'objet est spécifique, il faut qu'on sache s'il l'est pour moi
                        //la difficulté c'est que selon le type de l'objet (bdc / anr) on va devoir piocher l'info de manière un peu différente
                        if ($object->type == ObjectService::BDC) { //dans ce cas on vérifie que l'objet a des réplicats pour ce modèle
                            if ($context == self::BACK_OFFICE) {
                                $authorized = true;
                            } else {
                                if (!is_null($object->id)) {
                                    if (count($this->get('objectTable')->findByTypeSourceAnr(ObjectService::ANR, $object->id, $model->anr->id))) {
                                        $authorized = true;
                                    }
                                }
                            }
                        } else { //l'objet est de type anr
                            if ($context == self::BACK_OFFICE) { //si on est en back on laisse passé
                                $authorized = true;
                            }

                        }
                    } else {
                        $authorized = true;
                    }
                }
            }
        }

        if (!$authorized) {
            throw new \Exception('Bad mode for this object or models attached to asset incoherent with this object', 412);
        }
    }

     /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data){
        if (isset($data['isRegulator']) && isset($data['isGeneric']) &&
            $data['isRegulator'] && $data['isGeneric']) {
            throw new \Exception("A regulator model may not be generic", 412);
        }

        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        parent::update($id, $data);
    }

    protected function resetCurrentDefault() {
        $this->get('table')->resetCurrentDefault();
    }
}