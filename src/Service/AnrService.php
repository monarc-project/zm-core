<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Helper\EncryptDecryptHelperTrait;
use Monarc\Core\Model\Entity;
use Monarc\Core\Table;

class AnrService
{
    use EncryptDecryptHelperTrait;

    private Table\AnrTable $anrTable;

    private Table\InstanceTable $instanceTable;

    private Table\InstanceConsequenceTable $instanceConsequenceTable;

    private Table\InstanceRiskTable $instanceRiskTable;

    private Table\InstanceRiskOpTable $instanceRiskOpTable;

    private Table\ScaleTable $scaleTable;

    private Table\ScaleImpactTypeTable $scaleImpactTypeTable;

    private Table\ScaleCommentTable $scaleCommentTable;

    private Table\OperationalRiskScaleTable $operationalRiskScaleTable;

    private Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable;

    private Table\OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    private Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable;

    private Table\TranslationTable $translationTable;

    private Table\SoaScaleCommentTable $soaScaleCommentTable;

    private Table\InstanceMetadataFieldTable $instanceMetadataFieldTable;

    private ScaleService $scaleService;

    private OperationalRiskScaleService $operationalRiskScaleService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\AnrTable $anrTable,
        Table\InstanceTable $instanceTable,
        Table\InstanceConsequenceTable $instanceConsequenceTable,
        Table\InstanceRiskTable $instanceRiskTable,
        Table\InstanceRiskOpTable $instanceRiskOpTable,
        Table\ScaleTable $scaleTable,
        Table\ScaleImpactTypeTable $scaleImpactTypeTable,
        Table\ScaleCommentTable $scaleCommentTable,
        Table\OperationalRiskScaleTable $operationalRiskScaleTable,
        Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        Table\OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        Table\TranslationTable $translationTable,
        Table\SoaScaleCommentTable $soaScaleCommentTable,
        Table\InstanceMetadataFieldTable $instanceMetadataFieldTable,
        ScaleService $scaleService,
        OperationalRiskScaleService $operationalRiskScaleService,
        ConnectedUserService $connectedUserService
    ) {
        $this->anrTable = $anrTable;
        $this->instanceTable = $instanceTable;
        $this->instanceConsequenceTable = $instanceConsequenceTable;
        $this->instanceRiskTable = $instanceRiskTable;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->scaleTable = $scaleTable;
        $this->scaleImpactTypeTable = $scaleImpactTypeTable;
        $this->scaleCommentTable = $scaleCommentTable;
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleTypeTable = $operationalRiskScaleTypeTable;
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->operationalInstanceRiskScaleTable = $operationalInstanceRiskScaleTable;
        $this->translationTable = $translationTable;
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->instanceMetadataFieldTable = $instanceMetadataFieldTable;
        $this->scaleService = $scaleService;
        $this->operationalRiskScaleService = $operationalRiskScaleService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function create($data): Entity\Anr
    {
        $anr = (new Entity\Anr())
            ->setLabels($data)
            ->setDescriptions($data)
            ->setCreator($this->connectedUser->getEmail());

        $this->anrTable->save($anr);

        $this->scaleService->create($anr, ['type' => Entity\ScaleSuperClass::TYPE_IMPACT, 'min' => 0, 'max' => 3]);
        $this->scaleService->create($anr, ['type' => Entity\ScaleSuperClass::TYPE_THREAT, 'min' => 0, 'max' => 4]);
        $this->scaleService->create(
            $anr,
            ['type' => Entity\ScaleSuperClass::TYPE_VULNERABILITY, 'min' => 0, 'max' => 3]
        );

        $this->operationalRiskScaleService->createScale($anr, Entity\OperationalRiskScaleSuperClass::TYPE_IMPACT, 0, 4);
        $this->operationalRiskScaleService->createScale(
            $anr,
            Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD,
            0,
            4
        );

        /** @var Entity\Anr $anr */
        return $anr;
    }

    /**
     * Note: Used only from the ModelService when a model is duplicated.
     */
    public function duplicate(Entity\Anr $anr): Entity\Anr
    {
        /** @var Entity\Anr $newAnr */
        $newAnr = Entity\Anr::constructFromObject($anr)->setCreator($this->connectedUser->getEmail());

        $suffix = ' (copy ' . date('m/d/Y à H:i') . ')';
        $newAnr->setLabels([
            'label1' => $newAnr->getLabel(1) . $suffix,
            'label2' => $newAnr->getLabel(2) . $suffix,
            'label3' => $newAnr->getLabel(3) . $suffix,
            'label4' => $newAnr->getLabel(4) . $suffix,
        ]);

        foreach ($newAnr->getObjects() as $object) {
            $newAnr->addObject($object);
        }

        foreach ($anr->getObjectCategories() as $objectCategory) {
            $newAnr->addObjectCategory($objectCategory);
        }

        $newScalesImpactTypes = $this->duplicateScales($newAnr, $anr);
        $newOperationalRisksScalesTypes = $this->duplicateOperationalRisksScales($newAnr, $anr);

        $this->duplicateSoaScaleComments($newAnr, $anr);

        $this->duplicateInstancesMetadataFields($newAnr, $anr);

        $this->duplicateTranslations($newAnr, $anr);

        $this->duplicateInstancesRisksAndConsequences(
            $newAnr,
            $anr,
            $newScalesImpactTypes,
            $newOperationalRisksScalesTypes
        );

        $this->anrTable->save($newAnr);

        return $newAnr;
    }

    /**
     * @param Entity\Anr $newAnr
     * @param Entity\Anr $anr
     * @param array|Entity\ScaleImpactType[] $newScaleImpactTypes
     *                                      New scale impact types with the keys mapped to the ones duplicated from.
     * @param array|Entity\OperationalRiskScaleType[] $newOperationalRisksScalesTypes
     *                                      New operational risks scale types with the old keys mapped.
     */
    private function duplicateInstancesRisksAndConsequences(
        Entity\Anr $newAnr,
        Entity\Anr $anr,
        array $newScaleImpactTypes,
        array $newOperationalRisksScalesTypes
    ): void {
        foreach ($this->instanceTable->findRootInstancesByAnrAndOrderByPosition($anr) as $rootInstance) {
            $newRootInstance = $this->duplicateInstance(
                $rootInstance,
                $newAnr,
                $newScaleImpactTypes,
                $newOperationalRisksScalesTypes
            );
            $this->duplicateChildrenInstances(
                $newRootInstance,
                $rootInstance,
                $newScaleImpactTypes,
                $newOperationalRisksScalesTypes
            );
        }
    }

    private function duplicateChildrenInstances(
        Entity\Instance $newInstance,
        Entity\Instance $instance,
        array $newScaleImpactTypes,
        array $newOperationalRisksScalesTypes
    ): void {
        foreach ($instance->getChildren() as $childInstance) {
            $newChildInstance = $this
                ->duplicateInstance(
                    $childInstance,
                    $newInstance->getAnr(),
                    $newScaleImpactTypes,
                    $newOperationalRisksScalesTypes
                )
                ->setParent($newInstance)
                ->setRoot($newInstance->isRoot() ? $newInstance : $newInstance->getRoot());
            $this->duplicateChildrenInstances(
                $newChildInstance,
                $childInstance,
                $newScaleImpactTypes,
                $newOperationalRisksScalesTypes
            );
        }
    }

    private function duplicateInstance(
        Entity\Instance $instance,
        Entity\Anr $newAnr,
        array $scaleImpactTypes,
        array $newOperationalRisksScalesTypes
    ): Entity\Instance {
        /** @var Entity\Instance $newInstance */
        $newInstance = Entity\Instance::constructFromObject($instance)
            ->setAnr($newAnr)
            ->setAsset($instance->getAsset())
            ->setObject($instance->getObject())
            ->setCreator($this->connectedUser->getEmail());

        $this->instanceTable->save($newInstance, false);

        $this->duplicateInstancesConsequences($newInstance, $instance, $scaleImpactTypes);
        $this->duplicateInstancesInformationalAndOperationalRisks(
            $newInstance,
            $instance,
            $newOperationalRisksScalesTypes
        );

        return $newInstance;
    }

    private function duplicateInstancesConsequences(
        Entity\Instance $newInstance,
        Entity\Instance $instance,
        array $scaleImpactTypes
    ): void {
        foreach ($instance->getInstanceConsequences() as $instanceConsequence) {
            if (!isset($scaleImpactTypes[$instanceConsequence->getScaleImpactType()->getId()])) {
                throw new \LogicException('Scale impact types have to be created before the instance consequences.');
            }

            $newInstanceConsequence = Entity\InstanceConsequence::constructFromObject($instanceConsequence)
                ->setAnr($newInstance->getAnr())
                ->setInstance($newInstance)
                ->setScaleImpactType($scaleImpactTypes[$instanceConsequence->getScaleImpactType()->getId()])
                ->setCreator($this->connectedUser->getEmail());

            $this->instanceConsequenceTable->save($newInstanceConsequence, false);
        }
    }

    private function duplicateInstancesInformationalAndOperationalRisks(
        Entity\Instance $newInstance,
        Entity\Instance $instance,
        array $newOperationalRisksScalesTypes
    ): void {
        foreach ($instance->getInstanceRisks() as $instanceRisk) {
            /** @var Entity\InstanceRisk $newInstanceRisk */
            $newInstanceRisk = Entity\InstanceRisk::constructFromObject($instanceRisk)
                ->setAnr($newInstance->getAnr())
                ->setInstance($newInstance)
                ->setAsset($instanceRisk->getAsset())
                ->setThreat($instanceRisk->getThreat())
                ->setVulnerability($instanceRisk->getVulnerability())
                ->setAmv($instanceRisk->getAmv())
                ->setCreator($this->connectedUser->getEmail());

            $this->instanceRiskTable->save($newInstanceRisk, false);
        }

        foreach ($instance->getOperationalInstanceRisks() as $operationalInstanceRisk) {
            /** @var Entity\InstanceRiskOp $newInstanceRiskOp */
            $newInstanceRiskOp = Entity\InstanceRiskOp::constructFromObject($operationalInstanceRisk)
                ->setAnr($newInstance->getAnr())
                ->setInstance($newInstance)
                ->setObject($operationalInstanceRisk->getObject())
                ->setRolfRisk($operationalInstanceRisk->getRolfRisk())
                ->setCreator($this->connectedUser->getEmail());

            foreach ($operationalInstanceRisk->getOperationalInstanceRiskScales() as $operationalInstanceRiskScale) {
                $operationalRiskScaleTypeId = $operationalInstanceRiskScale->getOperationalRiskScaleType()->getId();
                if (!isset($newOperationalRisksScalesTypes[$operationalRiskScaleTypeId])) {
                    throw new \LogicException(
                        'Operational risk scale types have to be created before the operational instance risk creation.'
                    );
                }

                $newOperationalInstanceRiskScale = Entity\OperationalInstanceRiskScale::constructFromObject(
                    $operationalInstanceRiskScale
                )
                    ->setAnr($newInstance->getAnr())
                    ->setOperationalInstanceRisk($newInstanceRiskOp)
                    ->setOperationalRiskScaleType($newOperationalRisksScalesTypes[$operationalRiskScaleTypeId])
                    ->setCreator($this->connectedUser->getEmail());

                $this->operationalInstanceRiskScaleTable->save($newOperationalInstanceRiskScale, false);
            }

            $this->instanceRiskOpTable->save($newInstanceRiskOp, false);
        }
    }

    /**
     * @return array New scale impact types with the keys mapped to the ones duplicated from.
     */
    private function duplicateScales(Entity\Anr $newAnr, Entity\Anr $anr): array
    {
        $newScaleImpactTypes = [];
        foreach ($this->scaleTable->findByAnr($anr) as $scale) {
            $newScale = Entity\Scale::constructFromObject($scale)
                ->setAnr($newAnr)
                ->setCreator($this->connectedUser->getEmail());

            foreach ($scale->getScaleImpactTypes() as $scaleImpactType) {
                $newScaleImpactTypes[$scaleImpactType->getId()] = Entity\ScaleImpactType::constructFromObject(
                    $scaleImpactType
                )->setAnr($newAnr)->setScale($newScale)->setCreator($this->connectedUser->getEmail());

                $this->scaleImpactTypeTable->save($newScaleImpactTypes[$scaleImpactType->getId()], false);
            }

            foreach ($scale->getScaleComments() as $scaleComment) {
                /** @var Entity\ScaleComment $newScaleComment */
                $newScaleComment = Entity\ScaleComment::constructFromObject($scaleComment)
                    ->setScale($newScale)
                    ->setAnr($newAnr)
                    ->setCreator($this->connectedUser->getEmail());
                if ($scaleComment->getScaleImpactType() !== null) {
                    $newScaleComment->setScaleImpactType(
                        $newScaleImpactTypes[$scaleComment->getScaleImpactType()->getId()]
                    );
                }

                $this->scaleCommentTable->save($newScaleComment, false);
            }

            $this->scaleTable->save($newScale, false);
        }

        return $newScaleImpactTypes;
    }

    /**
     * @return array New operations risks scales types with the keys mapped to the ones duplicated from.
     */
    private function duplicateOperationalRisksScales(Entity\Anr $newAnr, Entity\Anr $anr): array
    {
        /** @var Entity\OperationalRiskScaleType[] $newOperationalRisksScaleTypes */
        $newOperationalRisksScaleTypes = [];
        /** @var Entity\OperationalRiskScale $operationalRiskScale */
        foreach ($this->operationalRiskScaleTable->findByAnr($anr) as $operationalRiskScale) {
            $newOperationalRiskScale = Entity\OperationalRiskScale::constructFromObject($operationalRiskScale)
                ->setAnr($newAnr)
                ->setCreator($this->connectedUser->getEmail());

            foreach ($operationalRiskScale->getOperationalRiskScaleTypes() as $operationalRiskScaleType) {
                $newOperationalRisksScaleTypes[$operationalRiskScaleType->getId()] =
                    Entity\OperationalRiskScaleType::constructFromObject($operationalRiskScaleType)
                    ->setAnr($newAnr)
                    ->setOperationalRiskScale($newOperationalRiskScale)
                    ->setCreator($this->connectedUser->getEmail());

                $this->operationalRiskScaleTypeTable->save(
                    $newOperationalRisksScaleTypes[$operationalRiskScaleType->getId()],
                    false
                );
            }

            foreach ($operationalRiskScale->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                $newOperationalRiskScaleComment = Entity\OperationalRiskScaleComment::constructFromObject(
                    $operationalRiskScaleComment
                )
                    ->setAnr($newAnr)
                    ->setOperationalRiskScale($newOperationalRiskScale)
                    ->setCreator($this->connectedUser->getEmail());
                if ($operationalRiskScaleComment->getOperationalRiskScaleType() !== null) {
                    $scaleTypeId = $operationalRiskScaleComment->getOperationalRiskScaleType()->getId();
                    $newOperationalRiskScaleComment->setOperationalRiskScaleType(
                        $newOperationalRisksScaleTypes[$scaleTypeId]
                    );
                }

                $this->operationalRiskScaleCommentTable->save($newOperationalRiskScaleComment, false);
            }

            $this->operationalRiskScaleTable->save($newOperationalRiskScale, false);
        }

        return $newOperationalRisksScaleTypes;
    }

    private function duplicateTranslations(Entity\Anr $newAnr, Entity\Anr $anr): void
    {
        /** @var Entity\Translation $translation */
        foreach ($this->translationTable->findByAnr($anr) as $translation) {
            $newTranslation = Entity\Translation::constructFromObject($translation)
                ->setAnr($newAnr)
                ->setCreator($this->connectedUser->getEmail());

            $this->translationTable->save($newTranslation, false);
        }
    }

    private function duplicateSoaScaleComments(Entity\Anr $newAnr, Entity\Anr $anr): void
    {
        /** @var Entity\SoaScaleComment $soaScaleComment */
        foreach ($this->soaScaleCommentTable->findByAnr($anr) as $soaScaleComment) {
            $newSoaScaleComment = Entity\SoaScaleComment::constructFromObject($soaScaleComment)
                ->setAnr($newAnr)
                ->setCreator($this->connectedUser->getEmail());

            $this->soaScaleCommentTable->save($newSoaScaleComment, false);
        }
    }

    private function duplicateInstancesMetadataFields(Entity\Anr $newAnr, Entity\Anr $anr): void
    {
        /** @var Entity\InstanceMetadataField $instanceMetadataField */
        foreach ($this->instanceMetadataFieldTable->findByAnr($anr) as $instanceMetadataField) {
            $newInstanceMetadataField = Entity\InstanceMetadataField::constructFromObject($instanceMetadataField)
                ->setAnr($newAnr)
                ->setCreator($this->connectedUser->getEmail());

            $this->instanceMetadataFieldTable->save($newInstanceMetadataField, false);
        }
    }
}
