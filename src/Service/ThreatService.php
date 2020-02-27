<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\ThreatTable;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package Monarc\Core\Service
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

    public function create($data, $last = true)
    {
        /** @var ThreatTable $threatTable */
        $threatTable = $this->get('table');
        $entityClass = $threatTable->getEntityClass();

        /** @var Threat $threat */
        $threat = new $entityClass();

        if (!empty($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById($data['anr']);

            $threat->setAnr($anr);
        }

        $threat->exchangeArray($data);
        $this->setDependencies($threat, $this->dependencies);

        $threat->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $threatTable->save($threat, $last);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        /** @var Threat $threat */
        $threat = $this->get('table')->getEntity($id);
        $threat->setDbAdapter($this->get('table')->getDb());
        $threat->setLanguage($this->getLanguage());

        $needUpdateRisks = ($threat->c != $data['c'] || $threat->i != $data['i'] || $threat->a != $data['a']);

        if ($threat->mode == Threat::MODE_SPECIFIC && $data['mode'] == Threat::MODE_GENERIC) {
            //delete models
            unset($data['models']);
        }

        $models = $data['models'] ?? [];
        $follow = $data['follow'] ?? null;
        unset($data['models'], $data['follow']);

        $threat->exchangeArray($data);
        if ($threat->get('models')) {
            $threat->get('models')->initialize();
        }

        if (!$this->get('amvService')->checkAMVIntegrityLevel($models, null, $threat, null, $follow)) {
            throw new \Monarc\Core\Exception\Exception('Integrity AMV links violation', 412);
        }

        if (($follow) && (!$this->get('amvService')->ensureAssetsIntegrityIfEnforced($models, null, $threat, null))) {
            throw new \Monarc\Core\Exception\Exception('Assets Integrity', 412);
        }

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($threat, $dependencies);

        switch ($threat->get('mode')) {
            case Threat::MODE_SPECIFIC:
                if (empty($models)) {
                    $threat->set('models', []);
                } else {
                    $modelsObj = [];
                    foreach ($models as $mid) {
                        $modelsObj[] = $this->get('modelTable')->getEntity($mid);
                    }
                    $threat->set('models', $modelsObj);
                }
                if ($follow) {
                    $this->get('amvService')->enforceAMVtoFollow($threat->get('models'), null, $threat, null);
                }
                break;
            case Threat::MODE_GENERIC:
                $threat->set('models', []);
                break;
        }

        $threat->setUpdater($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

        $id = $this->get('table')->save($threat);

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
