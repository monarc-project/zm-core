<?php
namespace MonarcCore\Model\Table;

class ModelTable extends AbstractEntityTable {

    public function resetCurrentDefault() {
        $defaults = $this->getEntityByFields(['isDefault' => true]);

        // There should only ever be one default model
        if (count($defaults) == 1) {
            /** @var \MonarcCore\Model\Entity\Model $def */
            $def = $defaults[0];

            $def->set('isDefault', 0);
            $this->save($def);
        }
    }

    /**
     * Get By Anrs
     *
     * @param $anrsId
     * @return array
     */
    public function getByAnrs($anrsId) {
        if(empty($anrsId)){
            $anrsId[] = 0;
        }

        $qb = $this->getRepository()->createQueryBuilder('m');

        return $qb
            ->select()
            ->where($qb->expr()->in('m.anr', $anrsId))
            ->getQuery()
            ->getResult();
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

        $asset = $object->asset;

        $authorized = false;

        if ($model->isGeneric) {
            if ($object->mode == \MonarcCore\Model\Entity\Model::MODE_GENERIC) {
                $authorized = true;
            }
        } else {
            if ($model->isRegulator) { //model is specific and regulated
                if ($asset->mode == \MonarcCore\Model\Entity\Model::MODE_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                }
            } else { //can receive generic or specifi to himself
                if ($asset->mode == \MonarcCore\Model\Entity\Model::MODE_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                } else {
                    if ($object->mode == \MonarcCore\Model\Entity\Model::MODE_SPECIFIC) { //aïe, l'objet est spécifique, il faut qu'on sache s'il l'est pour moi
                        //la difficulté c'est que selon le type de l'objet (bdc / anr) on va devoir piocher l'info de manière un peu différente
                        $objectType = 'bdc';
                        foreach($object->anrs as $anr) {
                            if ($anr->id == $model->anr->id) {
                                $objectType = 'anr';
                            }
                        }
                        if ($objectType == 'bdc') { //dans ce cas on vérifie que l'objet a des réplicats pour ce modèle
                            if ($context == \MonarcCore\Model\Entity\Model::BACK_OFFICE) {
                                $authorized = true;
                            } else {
                                if (!is_null($object->id)) {
                                    $authorized = false;
                                    $class = get_class($object);
                                    $objectsSource = $object->getDb()->fetchByFields($class,['source' => $object->id]);
                                    foreach($objectsSource as $source) {
                                        foreach($source->anrs as $anr) {
                                            if ($anr->id == $model->anr->id) {
                                                $authorized = true;
                                            }
                                        }
                                    }
                                }
                            }
                        } else { //l'objet est de type anr
                            if ($context == \MonarcCore\Model\Entity\Model::BACK_OFFICE) { //si on est en back on laisse passé
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
}
