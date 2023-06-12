<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity;
use Monarc\Core\Service\Traits\ImpactVerificationTrait;
use Monarc\Core\Table;

class InstanceConsequenceService
{
    use ImpactVerificationTrait;

    private Table\InstanceConsequenceTable $instanceConsequenceTable;

    private Table\InstanceTable $instanceTable;

    private Table\ScaleTable $scaleTable;

    private Table\ScaleImpactTypeTable $scaleImpactTypeTable;

    private InstanceService $instanceService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\InstanceConsequenceTable $instanceConsequenceTable,
        Table\InstanceTable $instanceTable,
        Table\ScaleTable $scaleTable,
        Table\ScaleImpactTypeTable $scaleImpactTypeTable,
        InstanceService $instanceService,
        ConnectedUserService $connectedUserService
    ) {
        $this->instanceConsequenceTable = $instanceConsequenceTable;
        $this->instanceTable = $instanceTable;
        $this->scaleTable = $scaleTable;
        $this->scaleImpactTypeTable = $scaleImpactTypeTable;
        $this->instanceService = $instanceService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getConsequencesData(Entity\InstanceSuperClass $instance, bool $includeScaleComments = false): array
    {
        $languageIndex = $this->getLanguageIndex($instance->getAnr());

        $result = [];
        foreach ($instance->getInstanceConsequences() as $instanceConsequence) {
            $scaleImpactType = $instanceConsequence->getScaleImpactType();
            if (!$scaleImpactType->isHidden()) {
                $consequenceData = [
                    'id' => $instanceConsequence->getId(),
                    'scaleImpactTypeId' => $scaleImpactType->getId(),
                    'scaleImpactType' => $scaleImpactType->getType(),
                    'scaleImpactTypeDescription1' => $scaleImpactType->getLabel(1),
                    'scaleImpactTypeDescription2' => $scaleImpactType->getLabel(2),
                    'scaleImpactTypeDescription3' => $scaleImpactType->getLabel(3),
                    'scaleImpactTypeDescription4' => $scaleImpactType->getLabel(4),
                    'c_risk' => $instanceConsequence->getConfidentiality(),
                    'i_risk' => $instanceConsequence->getIntegrity(),
                    'd_risk' => $instanceConsequence->getAvailability(),
                    'isHidden' => $instanceConsequence->isHidden(),
                ];
                if ($includeScaleComments) {
                    $consequenceData['comments'] = [];
                    foreach ($scaleImpactType->getScaleComments() as $scaleComment) {
                        $consequenceData['comments'][$scaleComment->getScaleValue()] = $scaleComment
                            ->getComment($languageIndex);
                    }
                }

                $result[] = $consequenceData;
            }
        }

        return $result;
    }

    /**
     * Creates the instance consequences based on a sibling instance's consequences or available scale impact types.
     */
    public function createInstanceConsequences(
        Entity\Instance $instance,
        Entity\Anr $anr,
        Entity\MonarcObject $object
    ): void {
        $siblingInstance = null;
        if ($object->isScopeGlobal()) {
            $siblingInstance = $this->instanceTable->findOneByAnrAndObjectExcludeInstance($anr, $object, $instance);
        }

        if ($siblingInstance !== null) {
            $instancesConsequences = $this->instanceConsequenceTable->findByAnrAndInstance($anr, $siblingInstance);
            foreach ($instancesConsequences as $instanceConsequence) {
                $this->createInstanceConsequence(
                    $instance,
                    $instanceConsequence->getScaleImpactType(),
                    $instanceConsequence->isHidden(),
                    [
                        'confidentiality' => $instanceConsequence->getConfidentiality(),
                        'integrity' => $instanceConsequence->getIntegrity(),
                        'availability' => $instanceConsequence->getAvailability(),
                    ]
                );
            }
        } else {
            /** @var Entity\ScaleImpactTypeSuperClass[] $scalesImpactTypes */
            $scalesImpactTypes = $this->scaleImpactTypeTable->findByAnr($anr);
            foreach ($scalesImpactTypes as $scalesImpactType) {
                if (!\in_array(
                    $scalesImpactType->getType(),
                    Entity\ScaleImpactTypeSuperClass::getScaleImpactTypesCid(),
                    true
                )) {
                    $this->createInstanceConsequence($instance, $scalesImpactType, $scalesImpactType->isHidden());
                }
            }
        }

        $this->instanceConsequenceTable->flush();
    }

    public function createInstanceConsequence(
        Entity\InstanceSuperClass $instance,
        Entity\ScaleImpactTypeSuperClass $scaleImpactType,
        bool $isHidden = false,
        array $evaluationCriteria = [],
        bool $saveInTheDb = false
    ): Entity\InstanceConsequenceSuperClass {
        $instanceConsequence = (new Entity\InstanceConsequence())
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setScaleImpactType($scaleImpactType)
            ->setIsHidden($isHidden)
            ->setCreator($this->connectedUser->getEmail());
        if (isset($evaluationCriteria['confidentiality'])) {
            $instanceConsequence->setConfidentiality($evaluationCriteria['confidentiality']);
        }
        if (isset($evaluationCriteria['integrity'])) {
            $instanceConsequence->setIntegrity($evaluationCriteria['integrity']);
        }
        if (isset($evaluationCriteria['availability'])) {
            $instanceConsequence->setAvailability($evaluationCriteria['availability']);
        }

        $this->instanceConsequenceTable->save($instanceConsequence, $saveInTheDb);

        return $instanceConsequence;
    }

    /**
     * This method is called from controllers to hide / show a specific consequence only linked to a specific instance.
     * The other place is InstanceService, to update an instance impacts (in this case $updateInstance = false).
     */
    public function patchConsequence(
        Entity\AnrSuperClass $anr,
        int $id,
        array $data,
        bool $updateInstance = true
    ): Entity\InstanceConsequence {
        /** @var Entity\InstanceConsequence $instanceConsequence */
        $instanceConsequence = $this->instanceConsequenceTable->findByIdAndAnr($id, $anr);

        $this->verifyImpacts($anr, $this->scaleTable, $data);

        $instanceConsequence
            ->setIsHidden((bool)$data['isHidden'])
            ->setUpdater($this->connectedUser->getEmail());
        $this->updateSiblingsConsequences($instanceConsequence, $updateInstance);

        if ($updateInstance) {
            $this->instanceService->refreshInstanceImpactAndUpdateRisks($instanceConsequence->getInstance());
        }

        $instanceConsequence->setUpdater($this->connectedUser->getEmail());

        $this->instanceConsequenceTable->save($instanceConsequence);

        return $instanceConsequence;
    }

    public function updateConsequencesByScaleImpactType(Entity\ScaleImpactType $scaleImpactType, bool $hide): void
    {
        $instancesConsequences = $this->instanceConsequenceTable->findByScaleImpactType($scaleImpactType);
        foreach ($instancesConsequences as $instanceConsequence) {
            $instanceConsequence->setIsHidden($hide)->setUpdater($this->connectedUser->getEmail());
            $this->instanceConsequenceTable->save($instanceConsequence, false);
        }
        $this->instanceConsequenceTable->flush();
    }

    protected function getLanguageIndex(Entity\AnrSuperClass $anr): int
    {
        return $this->connectedUser->getLanguage();
    }

    /**
     * Updates the consequences of the instances at the same level.
     */
    private function updateSiblingsConsequences(
        Entity\InstanceConsequence $instanceConsequence,
        bool $updateInstance
    ): void {
        $object = $instanceConsequence->getInstance()->getObject();
        if ($object->isScopeGlobal()) {
            $anr = $instanceConsequence->getInstance()->getAnr();
            $siblingInstances = $this->instanceTable->findByAnrAndObject($anr, $object);

            foreach ($siblingInstances as $siblingInstance) {
                $siblingInstanceConsequences = $this->instanceConsequenceTable->findByAnrInstanceAndScaleImpactType(
                    $anr,
                    $siblingInstance,
                    $instanceConsequence->getScaleImpactType()
                );

                foreach ($siblingInstanceConsequences as $siblingInstanceConsequence) {
                    $siblingInstanceConsequence
                        ->setIsHidden($instanceConsequence->isHidden())
                        ->setConfidentiality($instanceConsequence->getConfidentiality())
                        ->setIntegrity($instanceConsequence->getIntegrity())
                        ->setAvailability($instanceConsequence->getAvailability());

                    $this->instanceConsequenceTable->save($siblingInstanceConsequence, false);
                }

                if ($updateInstance) {
                    $this->instanceService->refreshInstanceImpactAndUpdateRisks($siblingInstance);
                }
            }
        }
    }
}
