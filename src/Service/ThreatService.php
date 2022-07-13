<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Table;

class ThreatService
{
    private Table\ThreatTable $threatTable;

    private InstanceRiskTable $instanceRiskTable;

    private Table\ModelTable $modelTable;

    private Table\ThemeTable $themeTable;

    private InstanceRiskService $instanceRiskService;

    private AmvService $amvService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\ThreatTable $threatTable,
        InstanceRiskTable $instanceRiskTable,
        Table\ModelTable $modelTable,
        Table\ThemeTable $themeTable,
        InstanceRiskService $instanceRiskService,
        AmvService $amvService,
        ConnectedUserService $connectedUserService
    ) {
        $this->threatTable = $threatTable;
        $this->instanceRiskTable = $instanceRiskTable;
        $this->modelTable = $modelTable;
        $this->themeTable = $themeTable;
        $this->instanceRiskService = $instanceRiskService;
        $this->amvService = $amvService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];

        /** @var Entity\Threat[] $threats */
        $threats = $this->threatTable->findByParams($params);
        foreach ($threats as $threat) {
            $result[] = $this->prepareThreatDataResult($threat);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->threatTable->countByParams($params);
    }

    public function getThreatData(string $uuid): array
    {
        /** @var Entity\Threat $threat */
        $threat = $this->threatTable->findByUuid($uuid);

        return $this->prepareThreatDataResult($threat);
    }

    public function create(array $data, bool $saveInDb = true): Entity\Threat
    {
        $threat = (new Entity\Threat())
            ->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setConfidentiality((int)$data['c'])
            ->setIntegrity((int)$data['i'])
            ->setAvailability((int)$data['a'])
            ->setMode((int)$data['mode'])
            ->setCreator($this->connectedUser->getEmail());
        if (isset($data['uuid'])) {
            $threat->setUuid($data['uuid']);
        }
        if (isset($data['status'])) {
            $threat->setStatus($data['status']);
        }

        if (!empty($data['theme'])) {
            /** @var Entity\Theme $theme */
            $theme = $data['theme'] instanceof Entity\Theme
                ? $data['theme']
                : $this->themeTable->findById((int)$data['theme']);

            $threat->setTheme($theme);
        }

        if (!empty($data['models']) && $threat->isModeSpecific()) {
            /** @var Entity\Model[] $models */
            $models = $this->modelTable->findByIds($data['models']);
            foreach ($models as $model) {
                $threat->addModel($model);
            }
        }

        $this->threatTable->save($threat, $saveInDb);

        return $threat;
    }

    public function update(string $uuid, array $data): void
    {
        /** @var Entity\Threat $threat */
        $threat = $this->threatTable->findByUuid($uuid);

        $areCiaChanged = $threat->getConfidentiality() !== (int)$data['c']
            || $threat->getIntegrity() !== (int)$data['i']
            || $threat->getAvailability() !== (int)$data['a'];

        $threat->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setConfidentiality((int)$data['c'])
            ->setIntegrity((int)$data['i'])
            ->setAvailability((int)$data['a'])
            ->setUpdater($this->connectedUser->getEmail());
        if (isset($data['mode'])) {
            $threat->setMode($data['mode']);
        }
        if (isset($data['status'])) {
            $threat->setStatus($data['status']);
        }
        if (isset($data['trend'])) {
            $threat->setTrend((int)$data['trend']);
        }
        if (isset($data['qualification'])) {
            $threat->setQualification((int)$data['qualification']);
        }
        if (isset($data['comment'])) {
            $threat->setComment($data['comment']);
        }

        $follow = isset($data['follow']) && (bool)$data['follow'];
        $modelsIds = $threat->isModeSpecific() && !empty($data['models'])
            ? $data['models']
            : [];

        if (!$this->amvService->checkAmvIntegrityLevel($modelsIds, null, $threat, null, $follow)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if ($follow && !$this->amvService->ensureAssetsIntegrityIfEnforced($modelsIds, null, $threat)) {
            throw new Exception('Assets Integrity', 412);
        }

        if (!empty($data['theme'])
            && ($threat->getTheme() === null
                || $threat->getTheme()->getId() !== (int)$data['theme']
            )
        ) {
            /** @var Entity\Theme $theme */
            $theme = $this->themeTable->findById((int)$data['theme']);
            $threat->setTheme($theme);
        }

        $threat->unlinkModels();
        if (!empty($modelsIds) && $threat->isModeSpecific()) {
            /** @var Entity\Model[] $models */
            $models = $this->modelTable->findByIds($modelsIds);
            foreach ($models as $model) {
                $threat->addModel($model);
            }
            if ($follow) {
                $this->amvService->enforceAmvToFollow($threat->getModels(), null, $threat);
            }
        }

        $this->threatTable->save($threat);

        if ($areCiaChanged) {
            $instancesRisks = $this->instanceRiskTable->findByThreat($threat);
            foreach ($instancesRisks as $instanceRisk) {
                $this->instanceRiskService->updateRisks($instanceRisk);
            }
            $this->instanceRiskTable->getDb()->flush();
        }
    }

    public function patch(string $uuid, array $data): void
    {
        /** @var Entity\Threat $threat */
        $threat = $this->threatTable->findByUuid($uuid);

        $threat->setStatus((int)$data['status'])
            ->setUpdater($this->connectedUser->getEmail());

        $this->threatTable->save($threat);
    }

    public function delete(string $uuid): void
    {
        $vulnerability = $this->threatTable->findByUuid($uuid);

        $this->threatTable->remove($vulnerability);
    }

    public function deleteList(array $data): void
    {
        $vulnerabilities = $this->threatTable->findByUuids($data);

        $this->threatTable->removeList($vulnerabilities);
    }

    private function prepareThreatDataResult(Entity\Threat $threat): array
    {
        $models = [];
        foreach ($threat->getModels() as $model) {
            $models[] = [
                'id' => $model->getId(),
            ];
        }
        $theme = null;
        if ($threat->getTheme() !== null) {
            $theme = [
                'id' => $threat->getTheme()->getId(),
                'label1' => $threat->getTheme()->getLabel(1),
                'label2' => $threat->getTheme()->getLabel(2),
                'label3' => $threat->getTheme()->getLabel(3),
                'label4' => $threat->getTheme()->getLabel(4),
            ];
        }

        return [
            'uuid' => $threat->getUuid(),
            'code' => $threat->getCode(),
            'label1' => $threat->getLabel(1),
            'label2' => $threat->getLabel(2),
            'label3' => $threat->getLabel(3),
            'label4' => $threat->getLabel(4),
            'description1' => $threat->getDescription(1),
            'description2' => $threat->getDescription(2),
            'description3' => $threat->getDescription(3),
            'description4' => $threat->getDescription(4),
            'c' => $threat->getConfidentiality(),
            'i' => $threat->getIntegrity(),
            'a' => $threat->getAvailability(),
            'theme' => $theme,
            'trend' => $threat->getTrend(),
            'qualification' => $threat->getQualification(),
            'mode' => $threat->getMode(),
            'comment' => $threat->getComment(),
            'models' => $models,
            'status' => $threat->getStatus(),
        ];
    }
}
