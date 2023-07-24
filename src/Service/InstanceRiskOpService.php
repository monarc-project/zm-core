<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity;
use Monarc\Core\Table;
use Monarc\Core\Model\Table\RolfTagTable;

class InstanceRiskOpService
{
    protected Table\InstanceTable $instanceTable;

    protected Table\InstanceRiskOpTable $instanceRiskOpTable;

    protected RolfTagTable $rolfTagTable;

    protected Entity\UserSuperClass $connectedUser;

    protected Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable;

    protected Table\TranslationTable $translationTable;

    protected TranslateService $translateService;

    protected ConfigService $configService;

    protected Table\OperationalRiskScaleTable $operationalRiskScaleTable;

    protected Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable;

    protected array $operationalRiskScales = [];

    public function __construct(
        Table\InstanceTable $instanceTable,
        Table\InstanceRiskOpTable $instanceRiskOpTable,
        Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        RolfTagTable $rolfTagTable,
        ConnectedUserService $connectedUserService,
        Table\TranslationTable $translationTable,
        TranslateService $translateService,
        Table\OperationalRiskScaleTable $operationalRiskScaleTable,
        Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        ConfigService $configService
    ) {
        $this->instanceTable = $instanceTable;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->operationalInstanceRiskScaleTable = $operationalInstanceRiskScaleTable;
        $this->rolfTagTable = $rolfTagTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->translationTable = $translationTable;
        $this->translateService = $translateService;
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleTypeTable = $operationalRiskScaleTypeTable;
        $this->configService = $configService;
    }

    public function createInstanceRisksOp(Entity\InstanceSuperClass $instance, Entity\ObjectSuperClass $object): void
    {
        if ($object->getRolfTag() === null || !$object->getAsset()->isPrimary()) {
            return;
        }

        $otherInstance = $this->instanceTable->findOneByAnrAndObjectExcludeInstance(
            $instance->getAnr(),
            $object,
            $instance
        );

        if ($otherInstance !== null && $object->isScopeGlobal()) {
            foreach ($this->instanceRiskOpTable->findByInstance($otherInstance) as $instanceRiskOp) {
                $newInstanceRiskOp = $this->getConstructedFromObjectInstanceRiskOp($instanceRiskOp)
                    ->setAnr($instance->getAnr())
                    ->setInstance($instance)
                    ->setObject($instanceRiskOp->getObject())
                    ->setRolfRisk($instanceRiskOp->getRolfRisk())
                    ->setCreator($this->connectedUser->getEmail());
                $this->instanceRiskOpTable->save($newInstanceRiskOp, false);

                $operationalInstanceRiskScales = $this->operationalInstanceRiskScaleTable->findByInstanceRiskOp(
                    $instanceRiskOp
                );
                foreach ($operationalInstanceRiskScales as $operationalInstanceRiskScale) {
                    $newOperationalInstanceRiskScale = $this
                        ->getConstructedFromObjectOperationalInstanceRiskScale($operationalInstanceRiskScale)
                        ->setCreator($this->connectedUser->getEmail());
                    $this->operationalInstanceRiskScaleTable->save($newOperationalInstanceRiskScale, false);
                }
            }
        } else {
            $rolfTag = $this->rolfTagTable->findById($object->getRolfTag()->getId());
            foreach ($rolfTag->getRisks() as $rolfRisk) {
                $this->createInstanceRiskOpWithScales(
                    $instance,
                    $object,
                    $rolfRisk
                );
            }
        }

        $this->instanceRiskOpTable->flush();
    }

    public function getOperationalRisks(Entity\AnrSuperClass $anr, int $instanceId = null, array $params = []): array
    {
        $instancesInfos = [];
        if ($instanceId === null) {
            $instances = $this->instanceTable->findByAnr($anr);
        } else {
            /** @var Entity\InstanceSuperClass $instance */
            $instance = $this->instanceTable->findById($instanceId);
            $instances = $this->extractInstanceAndChildrenInstances($instance);
        }
        foreach ($instances as $instance) {
            if ($instance->getAsset()->getType() === Entity\Asset::TYPE_PRIMARY) {
                $instancesInfos[$instance->getId()] = [
                    'id' => $instance->getId(),
                    'scope' => $instance->getObject()->getScope(),
                    'name1' => $instance->getName(1),
                    'name2' => $instance->getName(2),
                    'name3' => $instance->getName(3),
                    'name4' => $instance->getName(4)
                ];
            }
        }

        $instancesRisksOp = $this->instanceRiskOpTable->getInstancesRisksOp(
            $anr,
            array_keys($instancesInfos),
            $params
        );
        $scaleTypesTranslations = [];
        if (!empty($instancesRisksOp)) {
            $anr = current($instancesRisksOp)->getAnr();
            $scaleTypesTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
                $anr,
                [Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE],
                $this->getAnrLanguageCode($anr)
            );
        }
        $result = [];
        foreach ($instancesRisksOp as $instanceRiskOp) {
            $scalesData = [];
            foreach ($instanceRiskOp->getOperationalInstanceRiskScales() as $operationalInstanceRiskScale) {
                $scaleType = $operationalInstanceRiskScale->getOperationalRiskScaleType();
                $scalesData[$scaleType->getId()] = [
                    'instanceRiskScaleId' => $operationalInstanceRiskScale->getId(),
                    'label' => $scaleTypesTranslations[$scaleType->getLabelTranslationKey()]->getValue(),
                    'netValue' => $operationalInstanceRiskScale->getNetValue(),
                    'brutValue' => $operationalInstanceRiskScale->getBrutValue(),
                    'targetedValue' => $operationalInstanceRiskScale->getTargetedValue(),
                    'isHidden' => $scaleType->isHidden(),
                ];
            }

            $result[] = [
                'id' => $instanceRiskOp->getId(),
                'rolfRisk' => $instanceRiskOp->getRolfRisk() ? $instanceRiskOp->getRolfRisk()->getId() : null,
                'label1' => $instanceRiskOp->getRiskCacheLabel(1),
                'label2' => $instanceRiskOp->getRiskCacheLabel(2),
                'label3' => $instanceRiskOp->getRiskCacheLabel(3),
                'label4' => $instanceRiskOp->getRiskCacheLabel(4),
                'description1' => $instanceRiskOp->getRiskCacheDescription(1),
                'description2' => $instanceRiskOp->getRiskCacheDescription(2),
                'description3' => $instanceRiskOp->getRiskCacheDescription(3),
                'description4' => $instanceRiskOp->getRiskCacheDescription(4),
                'netProb' => $instanceRiskOp->getNetProb(),
                'brutProb' => $instanceRiskOp->getBrutProb(),
                'targetedProb' => $instanceRiskOp->getTargetedProb(),
                'cacheNetRisk' => $instanceRiskOp->getCacheNetRisk(),
                'cacheBrutRisk' => $instanceRiskOp->getCacheBrutRisk(),
                'cacheTargetedRisk' => $instanceRiskOp->getCacheTargetedRisk(),
                'scales' => $scalesData,
                'kindOfMeasure' => $instanceRiskOp->getKindOfMeasure(),
                'comment' => $instanceRiskOp->getComment(),
                't' => $instanceRiskOp->getKindOfMeasure() === Entity\InstanceRiskOpSuperClass::KIND_NOT_TREATED,
                'position' => $instanceRiskOp->getInstance()->getPosition(),
                'instanceInfos' => $instancesInfos[$instanceRiskOp->getInstance()->getId()] ?? [],
            ];
        }

        return $result;
    }

    public function updateScaleValue(Entity\AnrSuperClass $anr, int $id, array $data): Entity\InstanceRiskOpSuperClass
    {
        /** @var Entity\InstanceRiskOpSuperClass $operationalInstanceRisk */
        $operationalInstanceRisk = $this->instanceRiskOpTable->findByIdAndAnr($id, $anr);
        /** @var Entity\OperationalInstanceRiskScale $operationInstanceRiskScale */
        $operationInstanceRiskScale = $this->operationalInstanceRiskScaleTable->findByIdAndAnr(
            (int)$data['instanceRiskScaleId'],
            $anr
        );

        if (isset($data['netValue']) && $operationInstanceRiskScale->getNetValue() !== (int)$data['netValue']) {
            $this->verifyScaleValue($operationInstanceRiskScale, (int)$data['netValue']);
            $operationInstanceRiskScale->setNetValue((int)$data['netValue']);
        }
        if (isset($data['brutValue']) && $operationInstanceRiskScale->getBrutValue() !== (int)$data['brutValue']) {
            $this->verifyScaleValue($operationInstanceRiskScale, (int)$data['brutValue']);
            $operationInstanceRiskScale->setBrutValue((int)$data['brutValue']);
        }
        if (isset($data['targetedValue'])
            && $operationInstanceRiskScale->getTargetedValue() !== (int)$data['targetedValue']
        ) {
            $this->verifyScaleValue($operationInstanceRiskScale, (int)$data['targetedValue']);
            $operationInstanceRiskScale->setTargetedValue((int)$data['targetedValue']);
        }

        $operationInstanceRiskScale->setUpdater($this->connectedUser->getEmail());

        $this->updateRiskCacheValues($operationalInstanceRisk);

        $this->operationalInstanceRiskScaleTable->save($operationInstanceRiskScale);

        return $operationalInstanceRisk;
    }

    public function update(Entity\AnrSuperClass $anr, int $id, array $data): Entity\InstanceRiskOpSuperClass
    {
        /** @var Entity\InstanceRiskOpSuperClass $operationalInstanceRisk */
        $operationalInstanceRisk = $this->instanceRiskOpTable->findByIdAndAnr($id, $anr);

        if (isset($data['kindOfMeasure'])) {
            $operationalInstanceRisk->setKindOfMeasure((int)$data['kindOfMeasure']);
        }
        if (isset($data['comment'])) {
            $operationalInstanceRisk->setComment($data['comment']);
        }
        if (isset($data['netProb']) && $operationalInstanceRisk->getNetProb() !== $data['netProb']) {
            $this->verifyScaleProbabilityValue($operationalInstanceRisk->getAnr(), (int)$data['netProb']);
            $operationalInstanceRisk->setNetProb((int)$data['netProb']);
        }
        if (isset($data['brutProb']) && $operationalInstanceRisk->getBrutProb() !== $data['brutProb']) {
            $this->verifyScaleProbabilityValue($operationalInstanceRisk->getAnr(), (int)$data['brutProb']);
            $operationalInstanceRisk->setBrutProb((int)$data['brutProb']);
        }
        if (isset($data['targetedProb']) && $operationalInstanceRisk->getTargetedProb() !== $data['targetedProb']) {
            $this->verifyScaleProbabilityValue($operationalInstanceRisk->getAnr(), (int)$data['targetedProb']);
            $operationalInstanceRisk->setTargetedProb((int)$data['targetedProb']);
        }

        $operationalInstanceRisk->setUpdater($this->connectedUser->getEmail());

        $this->updateRiskCacheValues($operationalInstanceRisk);

        $this->instanceRiskOpTable->save($operationalInstanceRisk);

        return $operationalInstanceRisk;
    }

    public function updateRiskCacheValues(
        Entity\InstanceRiskOpSuperClass $operationalInstanceRisk,
        bool $flushChanges = false
    ): void {
        foreach (['Brut', 'Net', 'Targeted'] as $valueType) {
            $max = -1;
            $probVal = $operationalInstanceRisk->{'get' . $valueType . 'Prob'}();
            if ($probVal !== -1) {
                foreach ($operationalInstanceRisk->getOperationalInstanceRiskScales() as $riskScale) {
                    if ($riskScale->getOperationalRiskScaleType()->isHidden()) {
                        continue;
                    }
                    $scaleValue = $riskScale->{'get' . $valueType . 'Value'}();
                    if ($scaleValue > -1 && ($probVal * $scaleValue) > $max) {
                        $max = $probVal * $scaleValue;
                    }
                }
            }

            if ($operationalInstanceRisk->{'getCache' . $valueType . 'Risk'}() !== $max) {
                $operationalInstanceRisk
                    ->setUpdater($this->connectedUser->getFirstname() . ' ' . $this->connectedUser->getLastname())
                    ->{'setCache' . $valueType . 'Risk'}($max);
                $this->instanceRiskOpTable->save($operationalInstanceRisk, false);
            }
        }

        if ($flushChanges === true) {
            $this->instanceRiskOpTable->flush();
        }
    }

    public function createInstanceRiskOpWithScales(
        Entity\InstanceSuperClass $instance,
        Entity\ObjectSuperClass $object,
        Entity\RolfRiskSuperClass $rolfRisk,
        bool $flushChanges = false
    ): Entity\InstanceRiskOpSuperClass {
        $instanceRiskOp = $this->createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
            $instance,
            $object,
            $rolfRisk
        );

        $this->instanceRiskOpTable->save($instanceRiskOp, false);

        $operationalRiskScaleTypes = $this->operationalRiskScaleTypeTable->findByAnrAndScaleType(
            $instance->getAnr(),
            Entity\OperationalRiskScaleSuperClass::TYPE_IMPACT
        );
        foreach ($operationalRiskScaleTypes as $operationalRiskScaleType) {
            $operationalInstanceRiskScale = $this->createOperationalInstanceRiskScaleObject(
                $instanceRiskOp,
                $operationalRiskScaleType,
            );

            $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScale, false);
        }

        if ($flushChanges) {
            $this->instanceRiskOpTable->flush();
        }

        return $instanceRiskOp;
    }

    protected function createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
        Entity\InstanceSuperClass $instance,
        Entity\ObjectSuperClass $object,
        Entity\RolfRiskSuperClass $rolfRisk
    ): Entity\InstanceRiskOpSuperClass {
        return (new Entity\InstanceRiskOp())
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setObject($object)
            ->setRolfRisk($rolfRisk)
            ->setRiskCacheCode($rolfRisk->getCode())
            ->setRiskCacheLabels([
                'riskCacheLabel1' => $rolfRisk->getLabel(1),
                'riskCacheLabel2' => $rolfRisk->getLabel(2),
                'riskCacheLabel3' => $rolfRisk->getLabel(3),
                'riskCacheLabel4' => $rolfRisk->getLabel(4),
            ])
            ->setRiskCacheDescriptions([
                'riskCacheDescription1' => $rolfRisk->getDescription(1),
                'riskCacheDescription2' => $rolfRisk->getDescription(2),
                'riskCacheDescription3' => $rolfRisk->getDescription(3),
                'riskCacheDescription4' => $rolfRisk->getDescription(4),
            ]);
    }

    public function createOperationalInstanceRiskScaleObject(
        Entity\InstanceRiskOpSuperClass $instanceRiskOp,
        Entity\OperationalRiskScaleTypeSuperClass $operationalRiskScaleType
    ): Entity\OperationalInstanceRiskScaleSuperClass {
        return (new Entity\OperationalInstanceRiskScale())
            ->setAnr($instanceRiskOp->getAnr())
            ->setOperationalInstanceRisk($instanceRiskOp)
            ->setOperationalRiskScaleType($operationalRiskScaleType)
            ->setCreator($this->connectedUser->getEmail());
    }

    protected function getAnrLanguageCode(Entity\AnrSuperClass $anr): string
    {
        return strtolower($this->configService->getLanguageCodes()[$this->connectedUser->getLanguage()]);
    }

    protected function verifyScaleValue(
        Entity\OperationalInstanceRiskScaleSuperClass $operationalInstanceRiskScale,
        int $scaleValue
    ): void {
        $operationalRiskScaleType = $operationalInstanceRiskScale->getOperationalRiskScaleType();
        $allowedValues = [];
        foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
            if (!$operationalRiskScaleComment->isHidden()) {
                $allowedValues[] = $operationalRiskScaleComment->getScaleValue();
            }
        }

        if ($scaleValue !== -1 && !\in_array($scaleValue, $allowedValues, true)) {
            throw new Exception(sprintf(
                'The value %d should be between one of [%s]',
                $scaleValue,
                implode(', ', $allowedValues)
            ), 412);
        }
    }

    protected function verifyScaleProbabilityValue(Entity\AnrSuperClass $anr, int $scaleProbabilityValue): void
    {
        if (!isset($this->operationalRiskScales[Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD])) {
            $this->operationalRiskScales[Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD] =
                $this->operationalRiskScaleTable
                    ->findByAnrAndType($anr, Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD);
        }
        /* There is only one scale of the TYPE_LIKELIHOOD. */
        $operationalRiskScale = current(
            $this->operationalRiskScales[Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD]
        );
        if ($scaleProbabilityValue !== -1 && (
            $scaleProbabilityValue < $operationalRiskScale->getMin()
            || $scaleProbabilityValue > $operationalRiskScale->getMax()
        )) {
            throw new Exception(sprintf(
                'The value %d should be between %d and %d.',
                $scaleProbabilityValue,
                $operationalRiskScale->getMin(),
                $operationalRiskScale->getMax()
            ), 412);
        }
    }

    protected function getConstructedFromObjectInstanceRiskOp(
        Entity\InstanceRiskOpSuperClass $instanceRiskOp
    ): Entity\InstanceRiskOpSuperClass {
        return Entity\InstanceRiskOp::constructFromObject($instanceRiskOp);
    }

    protected function getConstructedFromObjectOperationalInstanceRiskScale(
        Entity\OperationalInstanceRiskScaleSuperClass $operationalInstanceRiskScale
    ): Entity\OperationalInstanceRiskScaleSuperClass {
        return Entity\OperationalInstanceRiskScale::constructFromObject($operationalInstanceRiskScale);
    }

    private function extractInstanceAndChildrenInstances(Entity\InstanceSuperClass $instance): array
    {
        $childInstances = [];
        foreach ($instance->getChildren() as $childInstance) {
            $childInstances = array_merge($childInstances, $this->extractInstanceAndChildrenInstances($childInstance));
        }

        return array_merge([$instance], $childInstances);
    }
}
