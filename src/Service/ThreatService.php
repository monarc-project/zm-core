<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\ThreatTable;
use Monarc\Core\Table\ModelTable;

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

    public function create($data, $saveInDb = true)
    {
        /** @var ThreatTable $threatTable */
        $threatTable = $this->get('table');
        $entityClass = $threatTable->getEntityClass();

        /** @var Threat $threat */
        $threat = new $entityClass();
        $threat->setLanguage($this->getLanguage());
        $threat->setDbAdapter($threatTable->getDb());

        if (!empty($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById($data['anr']);

            $threat->setAnr($anr);
        }

        $threat->exchangeArray($data);
        $this->setDependencies($threat, $this->dependencies);

        $threat->setCreator($this->getConnectedUser()->getEmail());

        return $threatTable->save($threat, $saveInDb);
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
        $follow = $data['follow'] ?? false;
        unset($data['models'], $data['follow']);

        $threat->exchangeArray($data);
        // TODO: we don't need to do this if set properly before -> change and drop drop.
        if ($threat->getModels()) {
            $threat->getModels()->initialize();
        }

        /** @var AmvService $amvService */
        $amvService = $this->get('amvService');
        if (!$amvService->checkAmvIntegrityLevel($models, null, $threat, null, $follow)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if ($follow && !$amvService->ensureAssetsIntegrityIfEnforced($models, null, $threat)) {
            throw new Exception('Assets Integrity', 412);
        }

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($threat, $dependencies);

        /** @var ModelTable $modelTable */
        $modelTable = $this->get('modelTable');
        $threat->unlinkModels();
        if ($threat->isModeSpecific()) {
            if (!empty($models)) {
                /** @var Model[] $modelsObj */
                $modelsObj = $modelTable->findByIds($models);
                foreach ($modelsObj as $model) {
                    $threat->addModel($model);
                }
            }
            if ($follow) {
                $amvService->enforceAmvToFollow($threat->getModels(), null, $threat);
            }
        }

        $threat->setUpdater($this->getConnectedUser()->getEmail());

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
