<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Table as DeprecatedTable;
use Monarc\Core\Service\Traits\ImpactVerificationTrait;
use Monarc\Core\Table;

class InstanceConsequenceService
{
    use ImpactVerificationTrait;

    private Table\InstanceConsequenceTable $instanceConsequenceTable;

    private Table\InstanceTable $instanceTable;

    private DeprecatedTable\ScaleCommentTable $scaleCommentTable;

    private DeprecatedTable\ScaleTable $scaleTable;

    private DeprecatedTable\ScaleImpactTypeTable $scaleImpactTypeTable;

    private InstanceService $instanceService;

    private Entity\User $connectedUser;

    public function __construct(
        Table\InstanceConsequenceTable $instanceConsequenceTable,
        Table\InstanceTable $instanceTable,
        DeprecatedTable\ScaleTable $scaleTable,
        DeprecatedTable\ScaleImpactTypeTable $scaleImpactTypeTable,
        DeprecatedTable\ScaleCommentTable $scaleCommentTable,
        InstanceService $instanceService,
        ConnectedUserService $connectedUserService
    ) {
        $this->instanceConsequenceTable = $instanceConsequenceTable;
        $this->instanceTable = $instanceTable;
        $this->scaleTable = $scaleTable;
        $this->scaleImpactTypeTable = $scaleImpactTypeTable;
        $this->scaleCommentTable = $scaleCommentTable;
        $this->instanceService = $instanceService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getConsequencesData(Entity\InstanceSuperClass $instance, bool $includeScaleComments = false): array
    {
        $anrLanguage = $instance->getAnr()->getLanguage();

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
                    $scalesComments = $this->scaleCommentTable->findByAnrAndScaleImpactType(
                        $instance->getAnr(),
                        $scaleImpactType
                    );

                    $consequenceData['comments'] = [];
                    foreach ($scalesComments as $scaleComment) {
                        $consequenceData['comments'][$scaleComment->getScaleValue()] = $scaleComment
                            ->getComment($anrLanguage);
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
        Entity\InstanceSuperClass $instance,
        Entity\AnrSuperClass $anr,
        Entity\ObjectSuperClass $object
    ): void {
        $siblingInstance = null;
        if ($object->isScopeGlobal()) {
            $siblingInstance = $this->instanceTable->findOneByAnrAndObjectExcludeInstance($anr, $object, $instance);
        }

        if ($siblingInstance !== null) {
            $instancesConsequences = $this->instanceConsequenceTable->findByAnrAndInstance($anr, $siblingInstance);
            foreach ($instancesConsequences as $instanceConsequence) {
                 $instanceConsequence = (new Entity\InstanceConsequence())
                    ->setAnr($anr)
                    ->setInstance($instance)
                    ->setScaleImpactType($instanceConsequence->getScaleImpactType())
                    ->setIsHidden($instanceConsequence->isHidden())
                    ->setConfidentiality($instanceConsequence->getConfidentiality())
                    ->setIntegrity($instanceConsequence->getIntegrity())
                    ->setAvailability($instanceConsequence->getAvailability())
                    ->setCreator($this->connectedUser->getEmail());

                $this->instanceConsequenceTable->save($instanceConsequence, false);
            }
        } else {
            $scalesImpactTypes = $this->scaleImpactTypeTable->findByAnr($anr);
            foreach ($scalesImpactTypes as $scalesImpactType) {
                if (!\in_array(
                    $scalesImpactType->getType(),
                    Entity\ScaleImpactTypeSuperClass::getScaleImpactTypesCid(),
                    true
                )) {
                     $instanceConsequence = (new Entity\InstanceConsequence())
                         ->setAnr($anr)
                         ->setInstance($instance)
                         ->setScaleImpactType($scalesImpactType)
                         ->setIsHidden($scalesImpactType->isHidden())
                         ->setCreator($this->connectedUser->getEmail());

                    $this->instanceConsequenceTable->save($instanceConsequence, false);
                }
            }
        }

        $this->instanceConsequenceTable->flush();
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

        $this->verifyImpactData($this->scaleTable->findByAnrAndType($anr, Entity\ScaleSuperClass::TYPE_IMPACT), $data);

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
