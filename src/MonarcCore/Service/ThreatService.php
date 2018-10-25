<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Threat;
use MonarcCore\Model\Table\InstanceRiskTable;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package MonarcCore\Service
 */
class ThreatService extends AbstractService
{
    protected $anrTable;
    protected $instanceRiskService;
    protected $instanceRiskTable;
    protected $modelTable;
    protected $modelService;
    protected $themeTable;
    protected $amvService;
    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];
    protected $dependencies = ['anr', 'theme', 'model[s]()'];
    protected $forbiddenFields = ['anr'];

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $entity = $this->get('entity');
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \MonarcCore\Exception\Exception('This risk analysis does not exist', 412);
            }
            $entity->setAnr($anr);
        }
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $themeId = $entity->get('theme');
        if (!empty($themeId)) {
            $theme = $this->get('themeTable')->getEntity($themeId);
            $entity->setTheme($theme);
        }

        $entity->status = 1;

        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        $needUpdateRisks = (($entity->c != $data['c']) || ($entity->i != $data['i']) || ($entity->a != $data['a']));

        if (($entity->mode == Threat::MODE_SPECIFIC) && ($data['mode'] == Threat::MODE_GENERIC)) {
            //delete models
            unset($data['models']);
        }

        $models = isset($data['models']) ? $data['models'] : [];
        $follow = isset($data['follow']) ? $data['follow'] : null;
        unset($data['models']);
        unset($data['follow']);

        $entity->exchangeArray($data);
        if ($entity->get('models')) {
            $entity->get('models')->initialize();
        }

        if (!$this->get('amvService')->checkAMVIntegrityLevel($models, null, $entity, null, $follow)) {
            throw new \MonarcCore\Exception\Exception('Integrity AMV links violation', 412);
        }

        if (($follow) && (!$this->get('amvService')->ensureAssetsIntegrityIfEnforced($models, null, $entity, null))) {
            throw new \MonarcCore\Exception\Exception('Assets Integrity', 412);
        }

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        switch ($entity->get('mode')) {
            case Threat::MODE_SPECIFIC:
                if (empty($models)) {
                    $entity->set('models', []);
                } else {
                    $modelsObj = [];
                    foreach ($models as $mid) {
                        $modelsObj[] = $this->get('modelTable')->getEntity($mid);
                    }
                    $entity->set('models', $modelsObj);
                }
                if ($follow) {
                    $this->get('amvService')->enforceAMVtoFollow($entity->get('models'), null, $entity, null);
                }
                break;
            case Threat::MODE_GENERIC:
                $entity->set('models', []);
                break;
        }

        $id = $this->get('table')->save($entity);

        if ($needUpdateRisks) {

            //retrieve instances risks
            /** @var InstanceRiskTable $instanceRiskTable */
            $instanceRiskTable = $this->get('instanceRiskTable');
            $instancesRisks = $instanceRiskTable->getEntityByFields(['threat' => $id]);

            /** @var InstanceRiskService $instanceRiskService */
            $instanceRiskService = $this->get('instanceRiskService');
            $i = 1;
            $nbInstancesRisks = count($instancesRisks);
            foreach ($instancesRisks as $instanceRisk) {
                $instanceRiskService->updateRisks($instanceRisk, ($i == $nbInstancesRisks));
                $i++;
            }
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }
}
