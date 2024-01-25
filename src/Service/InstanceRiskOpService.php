<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity;
use Monarc\Core\Service\Traits\OperationalRiskScaleVerificationTrait;
use Monarc\Core\Table;

class InstanceRiskOpService
{
    use OperationalRiskScaleVerificationTrait;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private Table\InstanceTable $instanceTable,
        private Table\InstanceRiskOpTable $instanceRiskOpTable,
        private Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        private Table\TranslationTable $translationTable,
        private Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        private ConfigService $configService,
        private OperationalRiskScaleService $operationalRiskScaleService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getOperationalRisks(Entity\AnrSuperClass $anr, int $instanceId = null, array $params = []): array
    {
        $instancesInfos = [];
        if ($instanceId === null) {
            $instances = $this->instanceTable->findByAnr($anr);
        } else {
            /** @var Entity\Instance $instance */
            $instance = $this->instanceTable->findByIdAndAnr($instanceId, $anr);
            $instances = $instance->getSelfAndChildrenInstances();
        }
        foreach ($instances as $instance) {
            if ($instance->getAsset()->getType() === Entity\AssetSuperClass::TYPE_PRIMARY) {
                $instancesInfos[$instance->getId()] = array_merge([
                    'id' => $instance->getId(),
                    'scope' => $instance->getObject()->getScope(),
                ], $instance->getNames());
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
                $this->getUiSelectedLanguageCode()
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
                'rolfRisk' => $instanceRiskOp->getRolfRisk()?->getId(),
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
                't' => $instanceRiskOp->isTreated(),
                'position' => $instanceRiskOp->getInstance()->getPosition(),
                'instanceInfos' => $instancesInfos[$instanceRiskOp->getInstance()->getId()] ?? [],
            ];
        }

        return $result;
    }

    public function createInstanceRisksOp(
        Entity\Instance $instance,
        Entity\MonarcObject $monarcObject,
        bool $saveInDb = true
    ): void {
        if ($monarcObject->getRolfTag() === null || !$monarcObject->getAsset()->isPrimary()) {
            return;
        }

        foreach ($monarcObject->getRolfTag()->getRisks() as $rolfRisk) {
            $this->createInstanceRiskOpWithScales($instance, $monarcObject, $rolfRisk);
        }

        if ($saveInDb) {
            $this->instanceRiskOpTable->flush();
        }
    }

    public function updateScaleValue(Entity\Anr $anr, int $id, array $data): Entity\InstanceRiskOp
    {
        /** @var Entity\InstanceRiskOp $operationalInstanceRisk */
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

    public function update(Entity\Anr $anr, int $id, array $data): Entity\InstanceRiskOp
    {
        /** @var Entity\InstanceRiskOp $operationalInstanceRisk */
        $operationalInstanceRisk = $this->instanceRiskOpTable->findByIdAndAnr($id, $anr);

        $likelihoodScale = $this->operationalRiskScaleService->getFromCacheOrFindLikelihoodScale($anr);
        if (isset($data['kindOfMeasure'])) {
            $operationalInstanceRisk->setKindOfMeasure((int)$data['kindOfMeasure']);
        }
        if (isset($data['comment'])) {
            $operationalInstanceRisk->setComment($data['comment']);
        }
        if (isset($data['netProb']) && $operationalInstanceRisk->getNetProb() !== $data['netProb']) {
            $this->verifyScaleProbabilityValue((int)$data['netProb'], $likelihoodScale);
            $operationalInstanceRisk->setNetProb((int)$data['netProb']);
        }
        if (isset($data['brutProb']) && $operationalInstanceRisk->getBrutProb() !== $data['brutProb']) {
            $this->verifyScaleProbabilityValue((int)$data['brutProb'], $likelihoodScale);
            $operationalInstanceRisk->setBrutProb((int)$data['brutProb']);
        }
        if (isset($data['targetedProb']) && $operationalInstanceRisk->getTargetedProb() !== $data['targetedProb']) {
            $this->verifyScaleProbabilityValue((int)$data['targetedProb'], $likelihoodScale);
            $operationalInstanceRisk->setTargetedProb((int)$data['targetedProb']);
        }

        $operationalInstanceRisk->setUpdater($this->connectedUser->getEmail());

        $this->updateRiskCacheValues($operationalInstanceRisk);

        $this->instanceRiskOpTable->save($operationalInstanceRisk);

        return $operationalInstanceRisk;
    }

    public function updateRiskCacheValues(Entity\InstanceRiskOp $operationalInstanceRisk): void
    {
        foreach (['Brut', 'Net', 'Targeted'] as $valueType) {
            $max = -1;
            $probVal = $operationalInstanceRisk->{'get' . $valueType . 'Prob'}();
            if ($probVal !== -1) {
                foreach ($operationalInstanceRisk->getOperationalInstanceRiskScales() as $riskScale) {
                    if (!$riskScale->getOperationalRiskScaleType()->isHidden()) {
                        $scaleValue = $riskScale->{'get' . $valueType . 'Value'}();
                        if ($scaleValue > -1 && ($probVal * $scaleValue) > $max) {
                            $max = $probVal * $scaleValue;
                        }
                    }
                }
            }

            if ($operationalInstanceRisk->{'getCache' . $valueType . 'Risk'}() !== $max) {
                $operationalInstanceRisk
                    ->setUpdater($this->connectedUser->getEmail())
                    ->{'setCache' . $valueType . 'Risk'}($max);
                $this->instanceRiskOpTable->save($operationalInstanceRisk, false);
            }
        }
    }

    public function createInstanceRiskOpWithScales(
        Entity\Instance $instance,
        Entity\MonarcObject $monarcObject,
        Entity\RolfRisk $rolfRisk
    ): Entity\InstanceRiskOp {
        /** @var Entity\InstanceRiskOp $instanceRiskOp */
        $instanceRiskOp = (new Entity\InstanceRiskOp())
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setObject($monarcObject)
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
            ])
            ->setCreator($this->connectedUser->getEmail());

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

        return $instanceRiskOp;
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

    private function getUiSelectedLanguageCode(): string
    {
        return strtolower($this->configService->getLanguageCodes()[$this->connectedUser->getLanguage()]);
    }
}
