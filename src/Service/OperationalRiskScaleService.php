<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity;
use Monarc\Core\Table;
use Ramsey\Uuid\Uuid;

class OperationalRiskScaleService
{
    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private Table\OperationalRiskScaleTable $operationalRiskScaleTable,
        private Table\OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        private Table\OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        private Table\TranslationTable $translationTable,
        private ConfigService $configService,
        private InstanceRiskOpService $instanceRiskOpService,
        private Table\InstanceRiskOpTable $instanceRiskOpTable,
        private Table\OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function createScale(Entity\Anr $anr, int $type, int $min, int $max): void
    {
        $scale = (new Entity\OperationalRiskScale())
            ->setAnr($anr)
            ->setType($type)
            ->setMin($min)
            ->setMax($max)
            ->setCreator($this->connectedUser->getEmail());

        $this->operationalRiskScaleTable->save($scale, false);

        $languageCodes = $this->getLanguageCodesForTranslations();

        $scaleType = null;
        if ($type === Entity\OperationalRiskScaleSuperClass::TYPE_IMPACT) {
            foreach (Entity\OperationalRiskScaleTypeSuperClass::getDefaultScalesImpacts() as $scalesImpactData) {
                $scaleType = $this->getCreatedOperationalRiskScaleTypeObject($anr, $scale);
                foreach ($languageCodes as $languageCode) {
                    $this->createTranslationObject(
                        $anr,
                        Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
                        $scaleType->getLabelTranslationKey(),
                        $languageCode,
                        $scalesImpactData[$languageCode]
                    );
                }

                for ($index = $min; $index <= $max; $index++) {
                    $this->createScaleComment($anr, $scale, $scaleType, $index, $index, $languageCodes);
                }
            }
        } else {
            for ($index = $min; $index <= $max; $index++) {
                $this->createScaleComment($anr, $scale, $scaleType, $index, $index, $languageCodes);
            }
        }

        $this->operationalRiskScaleTable->flush();
    }

    public function getOperationalRiskScales(Entity\Anr $anr, string $language): array
    {
        $result = [];
        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey($anr, [
            Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
            Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_COMMENT,
        ], $language);

        /** @var Entity\OperationalRiskScale $operationalRiskScale */
        foreach ($this->operationalRiskScaleTable->findByAnr($anr) as $operationalRiskScale) {
            $comments = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                if (!$operationalRiskScaleComment->isHidden()
                    && $operationalRiskScaleComment->getOperationalRiskScaleType() === null
                ) {
                    $translationComment = $translations[$operationalRiskScaleComment->getLabelTranslationKey()] ?? null;
                    $comments[] = [
                        'id' => $operationalRiskScaleComment->getId(),
                        'scaleId' => $operationalRiskScale->getId(),
                        'scaleTypeId' => null,
                        'scaleIndex' => $operationalRiskScaleComment->getScaleIndex(),
                        'scaleValue' => $operationalRiskScaleComment->getScaleValue(),
                        'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                    ];
                }
            }

            $types = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleTypes() as $operationalRiskScaleType) {
                $commentsOfType = [];
                foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $commentOfType) {
                    if (!$commentOfType->isHidden()) {
                        $translationComment = $translations[$commentOfType->getLabelTranslationKey()] ?? null;
                        $commentsOfType[] = [
                            'id' => $commentOfType->getId(),
                            'scaleId' => $operationalRiskScale->getId(),
                            'scaleTypeId' => $operationalRiskScaleType->getId(),
                            'scaleIndex' => $commentOfType->getScaleIndex(),
                            'scaleValue' => $commentOfType->getScaleValue(),
                            'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                        ];
                    }
                }

                $translationLabel = $translations[$operationalRiskScaleType->getLabelTranslationKey()] ?? null;
                $types[] = [
                    'id' => $operationalRiskScaleType->getId(),
                    'scaleId' => $operationalRiskScale->getId(),
                    'label' => $translationLabel !== null ? $translationLabel->getValue() : '',
                    'isHidden' => $operationalRiskScaleType->isHidden(),
                    'comments' => $commentsOfType,
                ];
            }

            $result[] = [
                'id' => $operationalRiskScale->getId(),
                'max' => $operationalRiskScale->getMax(),
                'min' => $operationalRiskScale->getMin(),
                'type' => $operationalRiskScale->getType(),
                'comments' => $comments,
                'scaleTypes' => $types,
            ];
        }

        return $result;
    }

    public function createOperationalRiskScaleType(Entity\Anr $anr, array $data): Entity\OperationalRiskScaleType
    {
        $languages = [];
        /** @var Entity\OperationalRiskScale $operationalRiskScale */
        $operationalRiskScale = $this->operationalRiskScaleTable->findByAnrAndScaleId($anr, (int)$data['scaleId']);

        $operationalRiskScaleType = $this->getCreatedOperationalRiskScaleTypeObject($anr, $operationalRiskScale);

        // Create a translations for the scale.
        foreach ($data['label'] as $lang => $labelText) {
            $this->createTranslationObject(
                $anr,
                Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
                $operationalRiskScaleType->getLabelTranslationKey(),
                $lang,
                (string)$labelText
            );

            $languages[] = $lang;
        }

        // Process the scale comments.
        if (!empty($data['comments'])) {
            foreach ($data['comments'] as $scaleCommentData) {
                $this->createScaleComment(
                    $anr,
                    $operationalRiskScale,
                    $operationalRiskScaleType,
                    $scaleCommentData['scaleIndex'],
                    $scaleCommentData['scaleValue'],
                    $languages
                );
            }
        }

        /* Link the new type with all the existed operational risks. */
        /** @var Entity\InstanceRiskOp $operationalInstanceRisk */
        foreach ($this->instanceRiskOpTable->findByAnr($anr) as $operationalInstanceRisk) {
            $operationalInstanceRiskScale = $this->instanceRiskOpService->createOperationalInstanceRiskScaleObject(
                $operationalInstanceRisk,
                $operationalRiskScaleType
            );
            $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScale, false);
        }

        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType);

        return $operationalRiskScaleType;
    }

    public function updateScaleType(Entity\Anr $anr, int $id, array $data): Entity\OperationalRiskScaleType
    {
        /** @var Entity\OperationalRiskScaleType $operationalRiskScaleType */
        $operationalRiskScaleType = $this->operationalRiskScaleTypeTable->findByIdAndAnr($id, $anr);

        if (isset($data['isHidden']) && (bool)$data['isHidden'] !== $operationalRiskScaleType->isHidden()) {
            $operationalRiskScaleType->setIsHidden((bool)$data['isHidden']);

            /* If the scale type visibility is changed, it's necessary to recalculate the total risk's values. */
            $this->recalculateTotalRisksValues($operationalRiskScaleType);
        }
        if (!empty($data['label'])) {
            $translationKey = $operationalRiskScaleType->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($operationalRiskScaleType->getAnr(), $translationKey, $data['language']);
                $translation->setValue($data['label']);
                $this->translationTable->save($translation, false);
            }
        }

        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType);

        return $operationalRiskScaleType;
    }

    public function deleteOperationalRiskScaleTypes(Entity\Anr $anr, array $data): void
    {
        $translationsKeys = [];

        foreach ($data as $id) {
            /** @var Entity\OperationalRiskScaleType $scaleTypeToDelete */
            $scaleTypeToDelete = $this->operationalRiskScaleTypeTable->findByIdAndAnr((int)$id, $anr);
            $translationsKeys[] = $scaleTypeToDelete->getLabelTranslationKey();

            foreach ($scaleTypeToDelete->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                $translationsKeys[] = $operationalRiskScaleComment->getLabelTranslationKey();
            }

            /* The scale type visibility is changed to exclude it from the calculation. */
            $scaleTypeToDelete->setIsHidden(true);
            $this->recalculateTotalRisksValues($scaleTypeToDelete);

            $this->operationalRiskScaleTable->remove($scaleTypeToDelete, false);
        }
        $this->operationalRiskScaleTable->flush();

        if (!empty($translationsKeys)) {
            $this->translationTable->deleteListByAnrAndKeys($anr, $translationsKeys);
        }
    }

    public function updateValueForAllScales(Entity\Anr $anr, array $data): void
    {
        $scaleIndex = (int)$data['scaleIndex'];
        $scaleValue = (int)$data['scaleValue'];

        $operationalRiskScaleComments = $this->operationalRiskScaleCommentTable->findByAnrAndScaleTypeOrderByIndex(
            $anr,
            Entity\OperationalRiskScaleSuperClass::TYPE_IMPACT
        );

        foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
            if ($operationalRiskScaleComment->getScaleIndex() < $scaleIndex
                && $operationalRiskScaleComment->getScaleValue() >= $scaleValue
            ) {
                $operationalRiskScaleComment->setScaleValue($scaleValue - 1);
                $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
            } elseif ($operationalRiskScaleComment->getScaleIndex() > $scaleIndex
                && $operationalRiskScaleComment->getScaleValue() <= $scaleValue
            ) {
                $operationalRiskScaleComment->setScaleValue($scaleValue + 1);
                $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
            } elseif ($operationalRiskScaleComment->getScaleIndex() === $scaleIndex) {
                $operationalRiskScaleComment->setScaleValue($scaleValue);
                $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
            }
        }

        $this->operationalRiskScaleCommentTable->flush();
    }

    public function updateLevelsNumberOfOperationalRiskScale(Entity\Anr $anr, array $data)
    {
        $languageCodes = $this->getLanguageCodesForTranslations();

        // Change the levels number of the scale.
        $levelsNumber = (int)$data['numberOfLevelForOperationalImpact'];

        /** @var Entity\OperationalRiskScale[] $operationalRiskScales */
        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            Entity\OperationalRiskScaleSuperClass::TYPE_IMPACT
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            foreach ($operationalRiskScale->getOperationalRiskScaleTypes() as $operationalRiskScaleType) {
                $operationalRiskScaleComments = $operationalRiskScaleType->getOperationalRiskScaleComments();

                $maxScaleValue = 0;
                foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
                    if ($operationalRiskScaleComment->getScaleIndex() < $levelsNumber) {
                        $operationalRiskScaleComment->setIsHidden(false);
                        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
                    } else {
                        $operationalRiskScaleComment->setIsHidden(true);
                        if ($operationalRiskScaleComment->getScaleValue() <= $maxScaleValue) {
                            $operationalRiskScaleComment->setScaleValue(++$maxScaleValue);
                        }
                        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
                    }
                    if ($operationalRiskScaleComment->getScaleValue() > $maxScaleValue) {
                        $maxScaleValue = $operationalRiskScaleComment->getScaleValue();
                    }
                }

                // Set -1, because the range is counted from 0.
                $operationalRiskScale->setMax($levelsNumber - 1);

                for ($index = $operationalRiskScale->getMin(); $index <= $operationalRiskScale->getMax(); $index++) {
                    if ($this->getCommentByIndex($index, $operationalRiskScaleComments) === null) {
                        $this->createScaleComment(
                            $anr,
                            $operationalRiskScale,
                            $operationalRiskScaleType,
                            $index,
                            ++$maxScaleValue,
                            $languageCodes
                        );
                    }
                }
            }

            $this->operationalRiskScaleTable->save($operationalRiskScale);
        }
    }

    public function updateMinMaxForOperationalRiskProbability(Entity\Anr $anr, $data): void
    {
        $probabilityMin = (int)$data['probabilityMin'];
        $probabilityMax = (int)$data['probabilityMax'];

        $languageCodes = $this->getLanguageCodesForTranslations();

        /** @var Entity\OperationalRiskScale[] $operationalRiskScales */
        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            Entity\OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $operationalRiskScaleComments = $operationalRiskScale->getOperationalRiskScaleComments();
            foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
                $scaleIndex = $operationalRiskScaleComment->getScaleIndex();
                if ($scaleIndex < $probabilityMin || $scaleIndex > $probabilityMax) {
                    $operationalRiskScaleComment->setIsHidden(true);
                } else {
                    $operationalRiskScaleComment->setIsHidden(false);
                }
                $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
            }
            for ($index = $probabilityMin; $index <= $probabilityMax; $index++) {
                if ($this->getCommentByIndex($index, $operationalRiskScaleComments) === null) {
                    $this->createScaleComment($anr, $operationalRiskScale, null, $index, $index, $languageCodes);
                }
            }

            $operationalRiskScale->setMin($probabilityMin)->setMax($probabilityMax);

            $this->operationalRiskScaleTable->save($operationalRiskScale);
        }
    }

    protected function getCreatedOperationalRiskScaleTypeObject(
        Entity\AnrSuperClass $anr,
        Entity\OperationalRiskScale $operationalRiskScale
    ): Entity\OperationalRiskScaleType {
        $operationalRiskScaleType = (new Entity\OperationalRiskScaleType())
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setAnr($anr)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setCreator($this->connectedUser->getEmail());

        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType, false);

        /** @var Entity\OperationalRiskScaleType */
        return $operationalRiskScaleType;
    }

    protected function createTranslationObject(
        Entity\Anr $anr,
        string $type,
        string $key,
        string $lang,
        string $value
    ): void {
        $translation = (new Entity\Translation())
            ->setAnr($anr)
            ->setType($type)
            ->setKey($key)
            ->setLang($lang)
            ->setValue($value)
            ->setCreator($this->connectedUser->getEmail());

        $this->translationTable->save($translation, false);
    }

    protected function createScaleComment(
        Entity\Anr $anr,
        Entity\OperationalRiskScale $operationalRiskScale,
        ?Entity\OperationalRiskScaleType $operationalRiskScaleType,
        int $scaleIndex,
        int $scaleValue,
        array $languageCodes
    ): void {
        /** @var Entity\OperationalRiskScaleComment $scaleComment */
        $scaleComment = (new Entity\OperationalRiskScaleComment())
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setAnr($anr)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setScaleIndex($scaleIndex)
            ->setScaleValue($scaleValue)
            ->setCreator($this->connectedUser->getEmail());
        if ($operationalRiskScaleType !== null) {
            $scaleComment->setOperationalRiskScaleType($operationalRiskScaleType);
        }

        $this->operationalRiskScaleCommentTable->save($scaleComment, false);

        foreach ($languageCodes as $languageCode) {
            // Create a translation for the scaleComment (init with blank value).
            $this->createTranslationObject(
                $anr,
                Entity\TranslationSuperClass::OPERATIONAL_RISK_SCALE_COMMENT,
                $scaleComment->getLabelTranslationKey(),
                $languageCode,
                ''
            );
        }
    }

    protected function getLanguageCodesForTranslations(): array
    {
        return $this->configService->getActiveLanguageCodes();
    }

    /**
     * @param int $index
     * @param Entity\OperationalRiskScaleComment[] $operationalRiskScaleComments
     */
    private function getCommentByIndex(
        int $index,
        iterable $operationalRiskScaleComments
    ): ?Entity\OperationalRiskScaleComment {
        foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
            if ($operationalRiskScaleComment->getScaleIndex() === $index) {
                return $operationalRiskScaleComment;
            }
        }

        return null;
    }

    private function recalculateTotalRisksValues(Entity\OperationalRiskScaleType $operationalRiskScaleType): void
    {
        $updatedInstanceRiskIds = [];
        foreach ($operationalRiskScaleType->getOperationalInstanceRiskScales() as $operationalInstanceRiskScale) {
            /** @var Entity\InstanceRiskOp $operationalInstanceRisk */
            $operationalInstanceRisk = $operationalInstanceRiskScale->getOperationalInstanceRisk();
            if (!\in_array($operationalInstanceRisk->getId(), $updatedInstanceRiskIds, true)) {
                $this->instanceRiskOpService->updateRiskCacheValues($operationalInstanceRisk);
                $updatedInstanceRiskIds[] = $operationalInstanceRisk->getId();
            }
        }
    }
}
