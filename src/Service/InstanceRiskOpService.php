<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskOwner;
use Monarc\Core\Model\Entity\InstanceRiskOwnerSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScale;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Monarc\Core\Model\Entity\OperationalRiskScaleTypeSuperClass;
use Monarc\Core\Model\Entity\RolfRiskSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceRiskOwnerTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\OperationalInstanceRiskScaleTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTypeTable;
use Monarc\Core\Model\Table\RolfTagTable;
use Monarc\Core\Model\Table\TranslationTable;

class InstanceRiskOpService
{
    protected AnrTable $anrTable;

    protected InstanceTable $instanceTable;

    protected InstanceRiskOpTable $instanceRiskOpTable;

    protected RolfTagTable $rolfTagTable;

    protected UserSuperClass $connectedUser;

    protected OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable;

    protected TranslationTable $translationTable;

    protected TranslateService $translateService;

    protected ConfigService $configService;

    protected OperationalRiskScaleTable $operationalRiskScaleTable;

    protected OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable;

    protected InstanceRiskOwnerTable $instanceRiskOwnerTable;

    protected array $operationalRiskScales = [];

    public function __construct(
        AnrTable $anrTable,
        InstanceTable $instanceTable,
        InstanceRiskOpTable $instanceRiskOpTable,
        OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        RolfTagTable $rolfTagTable,
        ConnectedUserService $connectedUserService,
        TranslationTable $translationTable,
        TranslateService $translateService,
        OperationalRiskScaleTable $operationalRiskScaleTable,
        OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        InstanceRiskOwnerTable $instanceRiskOwnerTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->instanceTable = $instanceTable;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->operationalInstanceRiskScaleTable = $operationalInstanceRiskScaleTable;
        $this->rolfTagTable = $rolfTagTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->translationTable = $translationTable;
        $this->translateService = $translateService;
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleTypeTable = $operationalRiskScaleTypeTable;
        $this->instanceRiskOwnerTable = $instanceRiskOwnerTable;
        $this->configService = $configService;
    }

    public function createInstanceRisksOp(InstanceSuperClass $instance, ObjectSuperClass $object): void
    {
        if ($object->getAsset() === null
            || $object->getRolfTag() === null
            || $object->getAsset()->getType() !== Asset::TYPE_PRIMARY
        ) {
            return;
        }

        $otherInstance = $this->instanceTable->findOneByAnrAndObjectExcludeInstance(
            $instance->getAnr(),
            $object,
            $instance
        );

        if ($otherInstance !== null && $object->isScopeGlobal()) {
            foreach ($this->instanceRiskOpTable->findByInstance($otherInstance) as $instanceRiskOp) {
                $newInstanceRiskOp = (clone $instanceRiskOp)
                    ->setAnr($instance->getAnr())
                    ->setInstance($instance)
                    ->setCreator($this->connectedUser->getFirstname() . ' ' . $this->connectedUser->getLastname());
                $this->instanceRiskOpTable->saveEntity($newInstanceRiskOp, false);

                $operationalInstanceRiskScales = $this->operationalInstanceRiskScaleTable->findByInstanceRiskOp(
                    $instanceRiskOp
                );
                foreach ($operationalInstanceRiskScales as $operationalInstanceRiskScale) {
                    $operationalInstanceRiskScaleClone = (clone $operationalInstanceRiskScale)
                        ->setCreator($this->connectedUser->getEmail());
                    $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScaleClone, false);
                }
            }
        } else {
            $rolfTag = $this->rolfTagTable->findById($object->getRolfTag()->getId());
            foreach ($rolfTag->getRisks() as $rolfRisk) {
                $instanceRiskOp = $this->createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
                    $instance,
                    $object,
                    $rolfRisk
                );

                $this->instanceRiskOpTable->saveEntity($instanceRiskOp, false);

                $operationalRiskScaleTypes = $this->operationalRiskScaleTypeTable->findByAnrAndScaleType(
                    $instance->getAnr(),
                    OperationalRiskScale::TYPE_IMPACT
                );
                foreach ($operationalRiskScaleTypes as $operationalRiskScaleType) {
                    $operationalInstanceRiskScale = $this->createOperationalInstanceRiskScaleObject(
                        $instanceRiskOp,
                        $operationalRiskScaleType,
                    );

                    $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScale, false);
                }
            }
        }

        $this->instanceRiskOpTable->getDb()->flush();
    }

    public function getOperationalRisks(int $anrId, int $instanceId = null, array $params = [])
    {
        $instancesInfos = [];
        if ($instanceId === null) {
            $instances = $this->instanceTable->findByAnrId($anrId);
        } else {
            $instance = $this->instanceTable->findById($instanceId);
            $this->instanceTable->initTree($instance);
            $instances = $this->extractInstanceAndChildInstances($instance);
        }
        foreach ($instances as $instance) {
            if ($instance->getAsset()->getType() === Asset::TYPE_PRIMARY) {
                $instancesInfos[$instance->getId()] = [
                    'id' => $instance->getId(),
                    'scope' => $instance->getObject()->getScope(),
                    'name1' => $instance->getName1(),
                    'name2' => $instance->getName2(),
                    'name3' => $instance->getName3(),
                    'name4' => $instance->getName4()
                ];
            }
        }

        $instancesRisksOp = $this->instanceRiskOpTable->getInstancesRisksOp(
            $anrId,
            array_keys($instancesInfos),
            $params
        );
        $scaleTypesTranslations = [];
        if (!empty($instancesRisksOp)) {
            $anr = current($instancesRisksOp)->getAnr();
            $scaleTypesTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
                $anr,
                [OperationalRiskScaleType::TRANSLATION_TYPE_NAME],
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
                't' => $instanceRiskOp->getKindOfMeasure() === InstanceRiskOp::KIND_NOT_TREATED,
                'position' => $instanceRiskOp->getInstance()->getPosition(),
                'instanceInfos' => $instancesInfos[$instanceRiskOp->getInstance()->getId()] ?? [],
                'context' => $instanceRiskOp->getContext(),
                'owner' => $instanceRiskOp->getInstanceRiskOwner()
                    ? $instanceRiskOp->getInstanceRiskOwner()->getName()
                    : '',
            ];
        }

        return $result;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteOperationalRisks(InstanceSuperClass $instance): void
    {
        $operationalRisks = $this->instanceRiskOpTable->findByInstance($instance);
        foreach ($operationalRisks as $operationalRisk) {
            $this->instanceRiskOpTable->deleteEntity($operationalRisk, false);
        }
        $this->instanceRiskOpTable->getDb()->flush();
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function updateScaleValue($id, $data): array
    {
        /** @var OperationalInstanceRiskScale $operationInstanceRiskScale */
        $operationInstanceRiskScale = $this->operationalInstanceRiskScaleTable->findById($data['instanceRiskScaleId']);
        if ($operationInstanceRiskScale === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(
                \get_class($this->operationalInstanceRiskScaleTable),
                $data['instanceRiskScaleId']
            );
        }

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

        $operationalInstanceRisk = $this->instanceRiskOpTable->findById($id);

        $this->updateRiskCacheValues($operationalInstanceRisk);

        $this->operationalInstanceRiskScaleTable->save($operationInstanceRiskScale);

        return $operationalInstanceRisk->getJsonArray();
    }

    /**
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws ORMException
     */
    public function update(int $id, array $data): array
    {
        // TODO: implement Permissions validator and inject it here. similar to \Monarc\Core\Service\AbstractService::deleteFromAnr

        /** @var InstanceRiskOpSuperClass $operationalInstanceRisk */
        $operationalInstanceRisk = $this->instanceRiskOpTable->findById($id);
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
        if (\array_key_exists('owner', $data)) {
            $this->processRiskOwnerName((string)$data['owner'], $operationalInstanceRisk);
        }
        if (isset($data['context'])) {
            $operationalInstanceRisk->setContext($data['context']);
        }

        $operationalInstanceRisk->setUpdater(
            $this->connectedUser->getFirstname() . ' ' . $this->connectedUser->getLastname()
        );

        $this->updateRiskCacheValues($operationalInstanceRisk);

        $this->instanceRiskOpTable->saveEntity($operationalInstanceRisk);

        return $operationalInstanceRisk->getJsonArray();
    }

    public function updateRiskCacheValues(
        InstanceRiskOpSuperClass $operationalInstanceRisk,
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
                $this->instanceRiskOpTable->saveEntity($operationalInstanceRisk, false);
            }
        }

        if ($flushChanges === true) {
            $this->instanceRiskOpTable->getDb()->flush();
        }
    }

    protected function createInstanceRiskOpObjectFromInstanceObjectAndRolfRisk(
        InstanceSuperClass $instance,
        ObjectSuperClass $object,
        RolfRiskSuperClass $rolfRisk
    ): InstanceRiskOpSuperClass {
        return (new InstanceRiskOp())
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
        InstanceRiskOpSuperClass $instanceRiskOp,
        OperationalRiskScaleTypeSuperClass $operationalRiskScaleType
    ): OperationalInstanceRiskScaleSuperClass {
        return (new OperationalInstanceRiskScale())
            ->setAnr($instanceRiskOp->getAnr())
            ->setOperationalInstanceRisk($instanceRiskOp)
            ->setOperationalRiskScaleType($operationalRiskScaleType)
            ->setCreator($this->connectedUser->getEmail());
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);
    }

    /**
     * @throws Exception
     */
    protected function verifyScaleValue(
        OperationalInstanceRiskScaleSuperClass $operationalInstanceRiskScale,
        int $scaleValue
    ): void {
        $operationalRiskScaleType = $operationalInstanceRiskScale->getOperationalRiskScaleType();
        $allowedValues = [];
        foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
            $allowedValues[] = $operationalRiskScaleComment->getScaleValue();
        }

        if ($scaleValue !== -1 && !\in_array($scaleValue, $allowedValues, true)) {
            throw new Exception(sprintf(
                'The value %d should be between one of [%s]',
                $scaleValue,
                implode(', ', $allowedValues)
            ), 412);
        }
    }

    /**
     * @throws Exception
     */
    protected function verifyScaleProbabilityValue(AnrSuperClass $anr, int $scaleProbabilityValue): void
    {
        if (!isset($this->operationalRiskScales[OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD])) {
            $this->operationalRiskScales[OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD] = $this
                ->operationalRiskScaleTable->findByAnrAndType($anr, OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD);
        }
        /* There is only one scale of the TYPE_LIKELIHOOD. */
        $operationalRiskScale = current($this->operationalRiskScales[OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD]);
        if ($scaleProbabilityValue !== -1
            && ($scaleProbabilityValue < $operationalRiskScale->getMin()
                || $scaleProbabilityValue > $operationalRiskScale->getMax()
            )
        ) {
            throw new Exception(sprintf(
                'The value %d should be between %d and %d.',
                $scaleProbabilityValue,
                $operationalRiskScale->getMin(),
                $operationalRiskScale->getMax()
            ), 412);
        }
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function processRiskOwnerName(
        string $ownerName,
        InstanceRiskOpSuperClass $operationalInstanceRisk
    ): void {
        if (empty($ownerName)) {
            $operationalInstanceRisk->setInstanceRiskOwner(null);
        } else {
            $instanceRiskOwner = $this->instanceRiskOwnerTable->findByAnrAndName(
                $operationalInstanceRisk->getAnr(),
                $ownerName
            );
            if ($instanceRiskOwner === null) {
                $instanceRiskOwner = $this->createInstanceRiskOwnerObject(
                    $operationalInstanceRisk->getAnr(),
                    $ownerName
                );

                $this->instanceRiskOwnerTable->save($instanceRiskOwner, false);

                $operationalInstanceRisk->setInstanceRiskOwner($instanceRiskOwner);
            } elseif ($operationalInstanceRisk->getInstanceRiskOwner() === null
                || $operationalInstanceRisk->getInstanceRiskOwner()->getId() !== $instanceRiskOwner->getId()
            ) {
                $operationalInstanceRisk->setInstanceRiskOwner($instanceRiskOwner);
            }
        }
    }

    protected function createInstanceRiskOwnerObject(AnrSuperClass $anr, string $ownerName): InstanceRiskOwnerSuperClass
    {
        return (new InstanceRiskOwner())
            ->setAnr($anr)
            ->setName($ownerName)
            ->setCreator($this->connectedUser->getEmail());
    }

    private function extractInstanceAndChildInstances(InstanceSuperClass $instance): array
    {
        $childInstances = [];
        foreach ($instance->getParameterValues('children') as $childInstance) {
            $childInstances = array_merge($childInstances, $this->extractInstanceAndChildInstances($childInstance));
        }

        return array_merge([$instance], $childInstances);
    }
}
