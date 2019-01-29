<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Class ModelTable
 * @package MonarcCore\Model\Table
 */
class ModelTable extends AbstractEntityTable
{
    /**
     * Reset Current Default
     */
    public function resetCurrentDefault()
    {
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
    public function getByAnrs($anrsId)
    {
        if (empty($anrsId)) {
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
     * @throws \MonarcCore\Exception\Exception
     */
    public function canAcceptObject($modelId, $object, $context = null, $forceAsset = null)
    {
        //retrieve data
        if (is_null($context) || $context == \MonarcCore\Model\Entity\AbstractEntity::BACK_OFFICE) {
            $model = $this->getEntity($modelId);

            $asset_mode = is_null($forceAsset) ? $object->get('asset')->get('mode') : $forceAsset->mode;

            if ($model->get('isGeneric') && $object->get('mode') == \MonarcCore\Model\Entity\MonarcObject::MODE_SPECIFIC) {
                throw new \MonarcCore\Exception\Exception('You cannot add a specific object to a generic model', 412);
            } else {
                if ($model->get('isRegulator')) {
                    if ($object->get('mode') == \MonarcCore\Model\Entity\MonarcObject::MODE_GENERIC) {
                        throw new \MonarcCore\Exception\Exception('You cannot add a generic object to a regulator model', 412);
                    } elseif ($object->get('mode') == \MonarcCore\Model\Entity\MonarcObject::MODE_SPECIFIC && $asset_mode == \MonarcCore\Model\Entity\MonarcObject::MODE_GENERIC) {
                        throw new \MonarcCore\Exception\Exception('You cannot add a specific object with generic asset to a regulator model', 412);
                    }
                }

                if (!$model->get('isGeneric') && $asset_mode == \MonarcCore\Model\Entity\MonarcObject::MODE_SPECIFIC) {
                    $models = is_null($forceAsset) ? $object->get('asset')->get('models') : $forceAsset->models;
                    $found = false;
                    foreach ($models as $m) {
                        if ($m->get('id') == $model->get('id')) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new \MonarcCore\Exception\Exception('You cannot add an object with specific asset unrelated to a ' . ($model->get('isRegulator') ? 'regulator' : 'specific') . ' model', 412);
                    }
                }
            }
        }
    }
}