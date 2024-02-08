<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table\ScaleImpactTypeTable;
use Monarc\Core\Table\InstanceTable;
use Monarc\Core\Table\ScaleTable;

class ScaleImpactTypeService
{
    use PositionUpdateTrait;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private ScaleImpactTypeTable $scaleImpactTypeTable,
        private ScaleTable $scaleTable,
        private InstanceTable $instanceTable,
        private InstanceService $instanceService,
        private InstanceConsequenceService $instanceConsequenceService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $formattedInputParams): array
    {
        $result = [];
        /** @var Entity\ScaleImpactType[] $scaleImpactTypes */
        $scaleImpactTypes = $this->scaleImpactTypeTable->findByParams($formattedInputParams);
        $scaleImpactTypesShortcuts = Entity\ScaleImpactTypeSuperClass::getScaleImpactTypesShortcuts();
        foreach ($scaleImpactTypes as $scaleImpactType) {
            $result[] = array_merge([
                'id' => $scaleImpactType->getId(),
                'isHidden' => (int)$scaleImpactType->isHidden(),
                'isSys' => (int)$scaleImpactType->isSys(),
                'position' => $scaleImpactType->getPosition(),
                'type' => $scaleImpactTypesShortcuts[$scaleImpactType->getType()] ?? 'CUS',
            ], $scaleImpactType->getLabels());
        }

        return $result;
    }

    public function create(Entity\Anr $anr, array $data, bool $saveInTheDb = true): Entity\ScaleImpactType
    {
        /** @var Entity\ScaleImpactType $scaleImpactType */
        $scaleImpactType = (new Entity\ScaleImpactType())
            ->setAnr($anr)
            ->setScale(
                $data['scale'] instanceof Entity\Scale ? $data['scale'] : $this->scaleTable->findById($data['scale'])
            )
            ->setLabels($data)
            ->setType($data['type'] ?? $this->scaleImpactTypeTable->findMaxPosition(['anr' => $anr]) + 1)
            ->setCreator($this->connectedUser->getEmail());
        if (isset($data['isSys'])) {
            $scaleImpactType->setIsSys((bool)$data['isSys']);
        }
        if (isset($data['isHidden'])) {
            $scaleImpactType->setIsHidden((bool)$data['isHidden']);
        }

        /* Create InstanceConsequence for each instance of the current anr. */
        /** @var Entity\Instance $instance */
        foreach ($this->instanceTable->findByAnr($scaleImpactType->getAnr()) as $instance) {
            $this->instanceConsequenceService->createInstanceConsequence($instance, $scaleImpactType);
        }

        if (empty($data['position'])) {
            $this->updatePositions($scaleImpactType, $this->scaleImpactTypeTable);
        } else {
            $scaleImpactType->setPosition($data['position']);
        }

        $this->scaleImpactTypeTable->save($scaleImpactType, $saveInTheDb);

        return $scaleImpactType;
    }

    /**
     * Hide/show or change name of scales impact types on the Evaluation scales page
     */
    public function patch(Entity\Anr $anr, int $id, array $data): Entity\ScaleImpactType
    {
        /** @var Entity\ScaleImpactType $scaleImpactType */
        $scaleImpactType = $this->scaleImpactTypeTable->findByIdAndAnr($id, $anr);

        if (isset($data['isHidden'])) {
            $scaleImpactType->setIsHidden((bool)$data['isHidden']);
            $this->instanceConsequenceService->updateConsequencesByScaleImpactType(
                $scaleImpactType,
                (bool)$data['isHidden']
            );
            $this->instanceService->refreshAllTheInstancesImpactAndUpdateRisks($anr);
        }

        $scaleImpactType->setLabels($data)->setUpdater($this->connectedUser->getEmail());

        $this->scaleImpactTypeTable->save($scaleImpactType);

        return $scaleImpactType;
    }

    public function delete(Entity\Anr $anr, int $id): void
    {
        /** @var Entity\ScaleImpactType $scaleImpactType */
        $scaleImpactType = $this->scaleImpactTypeTable->findByIdAndAnr($id, $anr);
        if ($scaleImpactType->isSys()) {
            throw new Exception('Default Scale Impact Types can\'t be removed.', '403');
        }

        $this->scaleImpactTypeTable->remove($scaleImpactType);
    }

    /** Called only from the BackOffice, ScaleService. */
    public function createDefaultScaleImpactTypes(Entity\Scale $scale): void
    {
        $defaultScaleImpactTypes = Entity\ScaleImpactTypeSuperClass::getDefaultScalesImpacts();
        $position = 1;
        foreach (Entity\ScaleImpactTypeSuperClass::getScaleImpactTypesShortcuts() as $type => $shortcut) {
            $this->create($scale->getAnr(), [
                'scale' => $scale,
                'type' => $type,
                'isSys' => true,
                'label1' => $defaultScaleImpactTypes['label1'][$shortcut],
                'label2' => $defaultScaleImpactTypes['label2'][$shortcut],
                'label3' => $defaultScaleImpactTypes['label3'][$shortcut],
                'label4' => $defaultScaleImpactTypes['label4'][$shortcut],
                'position' => $position++,
            ], false);
        }
    }
}
