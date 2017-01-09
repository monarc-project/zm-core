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
    public function canAcceptObject($modelId, $object, $context = null, $forceAsset = null)
    {
        //retrieve data
        if(is_null($context) || $context == \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE){
            $model = $this->getEntity($modelId);

            $asset_mode = is_null($forceAsset) ? $object->get('asset')->get('mode') : $forceAsset->mode;

            if ($model->get('isGeneric') && $object->get('mode') == \MonarcCore\Model\Entity\Object::MODE_SPECIFIC) {
                throw new \Exception('You cannot add a specific object to a generic model', 412);
            } else {
                if ($model->get('isRegulator')) {
                    if ($object->get('mode') == \MonarcCore\Model\Entity\Object::MODE_GENERIC) {
                        throw new \Exception('You cannot add a generic object to a regulator model', 412);
                    } elseif ($object->get('mode') == \MonarcCore\Model\Entity\Object::MODE_SPECIFIC && $asset_mode == \MonarcCore\Model\Entity\Object::MODE_GENERIC) {
                        throw new \Exception('You cannot add a specific object with generic asset to a regulator model', 412);
                    }
                }

                if (!$model->get('isGeneric') && $asset_mode == \MonarcCore\Model\Entity\Object::MODE_SPECIFIC) {
                    $models = is_null($forceAsset) ? $object->get('asset')->get('models') : $forceAsset->models;
                    $found = false;
                    foreach ($models as $m) {
                        if ($m->get('id') == $model->get('id')) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new \Exception('You cannot add an object with specific asset unrelated to a ' . ($model->get('isRegulator') ? 'regulator' : 'specific') . ' model', 412);
                    }
                }
            }
        }
    }
}
