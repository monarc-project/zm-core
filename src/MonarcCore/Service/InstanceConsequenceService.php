<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\MonarcObject;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
use Zend\EventManager\EventManager;

/**
 * Instance Consequence Service
 *
 * Class InstanceConsequenceService
 * @package MonarcCore\Service
 */
class InstanceConsequenceService extends AbstractService
{
    protected $dependencies = ['anr', 'instance', 'object', 'scaleImpactType'];
    protected $anrTable;
    protected $instanceTable;
    protected $MonarcObjectTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $forbiddenFields = ['anr', 'instance', 'object', 'scaleImpactType'];

    /**
     * @inheritdoc
     */
    public function patchConsequence($id, $data, $patchInstance = true, $local = true, $fromInstance = false)
    {
        $anrId = $data['anr'];

        if (count($data)) {
            $entity = $this->get('table')->getEntity($id);

            if (isset($data['isHidden'])) {
                if ($data['isHidden']) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                    if ($local) {
                        $data['locallyTouched'] = 1;
                    }
                } else {
                    if ($local) {
                        $data['locallyTouched'] = 0;
                    } else {
                        if ($entity->locallyTouched) {
                            $data['isHidden'] = 1;
                        }
                    }
                }
            } else {
                if ($entity->isHidden) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                }
            }

            $data = $this->updateConsequences($id, $data);

            $data['anr'] = $anrId;

            $this->verifyRates($anrId, $data, $this->getEntity($id));

            parent::patch($id, $data);

            $this->updateBrothersConsequences($anrId, $id);

            if ($patchInstance) {
                $this->updateInstanceImpacts($id, $fromInstance);
            }
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        if (empty($data)) {
            throw new \MonarcCore\Exception\Exception('Data missing', 412);
        }

        $anrId = $data['anr'];
        $data = $this->updateConsequences($id, $data);
        $data['anr'] = $anrId;

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $entity = $table->getEntity($id);

        $this->filterPostFields($data, $entity, ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh']);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $id = $this->get('table')->save($entity);

        $this->updateBrothersConsequences($anrId, $id);

        $this->updateInstanceImpacts($id);

        return $id;
    }

    /**
     * Update the consequences of the provided instance ID
     * @param int $id The instance ID
     * @param array $data The new values
     * @return array $data
     */
    protected function updateConsequences($id, $data)
    {
        $anrId = $data['anr'];
        unset($data['anr']);

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        return $data;
    }

    /**
     * Update the consequences of the instances at the same level
     * @param int $anrId The ANR ID
     * @param int $id THe instance consequence ID
     */
    public function updateBrothersConsequences($anrId, $id)
    {
        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceConsequence = $table->getEntity($id);

        if ($instanceConsequence->object->scope == MonarcObject::SCOPE_GLOBAL) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            $brothers = $instanceTable->getEntityByFields([
                'anr' => $anrId,
                'object' => $instanceConsequence->object->id
            ]);

            if (count($brothers) > 1) {
                foreach ($brothers as $brother) {

                    /** @var InstanceConsequenceTable $instanceConsequenceTable */
                    $instanceConsequenceTable = $this->get('table');
                    $brotherInstancesConsequences = $instanceConsequenceTable->getEntityByFields([
                        'anr' => $anrId,
                        'instance' => $brother->id,
                        'scaleImpactType' => $instanceConsequence->scaleImpactType->id
                    ]);

                    $i = 1;
                    $nbBrotherInstancesConsequences = count($brotherInstancesConsequences);
                    foreach ($brotherInstancesConsequences as $brotherInstanceConsequence) {
                        $brotherInstanceConsequence->isHidden = $instanceConsequence->isHidden;
                        $brotherInstanceConsequence->locallyTouched = $instanceConsequence->locallyTouched;
                        $brotherInstanceConsequence->c = $instanceConsequence->c;
                        $brotherInstanceConsequence->i = $instanceConsequence->i;
                        $brotherInstanceConsequence->d = $instanceConsequence->d;

                        $instanceConsequenceTable->save($brotherInstanceConsequence, ($i == $nbBrotherInstancesConsequences));
                        $i++;
                    }
                }
            }
        }
    }

    /**
     * Update Instance Impacts
     * @param int $instanceConsequencesId The instance consequences ID
     */
    public function updateInstanceImpacts($instanceConsequencesId, $fromInstance = false)
    {
        $class = $this->get('scaleImpactTypeTable')->getClass();
        $cidTypes = $class::getScaleImpactTypeCid();

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceCurrentConsequence = $table->getEntity($instanceConsequencesId);

        $instanceC = [];
        $instanceI = [];
        $instanceD = [];
        $instanceConsequences = $table->getEntityByFields(['instance' => $instanceCurrentConsequence->get('instance')->get('id')]);
        foreach ($instanceConsequences as $instanceConsequence) {
            if (!in_array($instanceConsequence->scaleImpactType->type, $cidTypes)) {
                $instanceC[] = (int)$instanceConsequence->get('c');
                $instanceI[] = (int)$instanceConsequence->get('i');
                $instanceD[] = (int)$instanceConsequence->get('d');
            }
        }

        $anrId = $instanceCurrentConsequence->anr->id;
        $instanceId = $instanceCurrentConsequence->instance->id;

        $data = [
            'c' => max($instanceC),
            'i' => max($instanceI),
            'd' => max($instanceD),
        ];


        $parent = $instanceCurrentConsequence->get('instance')->get('parent');
        foreach ($data as $k => $v) {
            $data[$k . 'h'] = ($v == -1) ? 1 : 0;
            if ($data[$k . 'h'] && !empty($parent)) { // hérité: on prend la valeur du parent
                $data[$k] = $parent->get($k);
            }
        }

        $data['anr'] = $anrId;

        if (!$fromInstance) {
            //if father instance exist, create instance for child
            $eventManager = new EventManager();
            $eventManager->setIdentifiers('instance');
            $sharedEventManager = $eventManager->getSharedManager();
            $eventManager->setSharedManager($sharedEventManager);
            $eventManager->trigger('patch', null, compact(['anrId', 'instanceId', 'data']));
        } else {
            $instance = $instanceCurrentConsequence->get('instance')->initialize();
            $instance->exchangeArray($data);
            $this->get('instanceTable')->save($instance);
        }
    }

    /**
     * Patch by Scale Impact Type
     * @param int $scaleImpactTypeId The scale impact type ID
     * @param array $data The new data to set
     */
    public function patchByScaleImpactType($scaleImpactTypeId, $data)
    {
        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('table');
        $instancesConsequences = $instanceConsequenceTable->getEntityByFields(['scaleImpactType' => $scaleImpactTypeId]);

        $consequencesIds = [];

        $i = 1;
        $nbInstancesConsequences = count($instancesConsequences);
        foreach ($instancesConsequences as $instanceConsequence) {
            $this->patchConsequence($instanceConsequence->id, $data, ($i == $nbInstancesConsequences), false);
            $consequencesIds[$instanceConsequence->id] = $instanceConsequence->id;
            $i++;
        }

        if (!empty($consequencesIds)) {
            foreach ($consequencesIds as $idc) {
                $this->updateInstanceImpacts($idc);
            }
        }
    }
}
