<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleCommentSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Monarc\Core\Model\Entity\OperationalRiskScaleTypeSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Table\OperationalInstanceRiskScaleTable;
use Monarc\Core\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Table\OperationalRiskScaleTable;
use Monarc\Core\Table\OperationalRiskScaleTypeTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class OperationalRiskScaleService
{
    protected UserSuperClass $connectedUser;

    protected OperationalRiskScaleTable $operationalRiskScaleTable;

    protected OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable;

    protected OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    private InstanceRiskOpService $instanceRiskOpService;

    private InstanceRiskOpTable $instanceRiskOpTable;

    private OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable;

    public function __construct(
        ConnectedUserService $connectedUserService,
        OperationalRiskScaleTable $operationalRiskScaleTable,
        OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService,
        InstanceRiskOpService $instanceRiskOpService,
        InstanceRiskOpTable $instanceRiskOpTable,
        OperationalInstanceRiskScaleTable $operationalInstanceRiskScaleTable
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleTypeTable = $operationalRiskScaleTypeTable;
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
        $this->instanceRiskOpService = $instanceRiskOpService;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->operationalInstanceRiskScaleTable = $operationalInstanceRiskScaleTable;
    }

    public function createScale(
        AnrSuperClass $anr,
        int $type,
        int $min,
        int $max
    ): void {
        $scale = (new OperationalRiskScale())
            ->setAnr($anr)
            ->setType($type)
            ->setMin($min)
            ->setMax($max)
            ->setCreator($this->connectedUser->getEmail());

        $this->operationalRiskScaleTable->save($scale, false);

        $languageCodes = $this->getLanguageCodesForTranslations();

        if ($type === OperationalRiskScaleSuperClass::TYPE_IMPACT) {
            $scaleType = $this->createOperationalRiskScaleTypeObject($anr, $scale);
            foreach ($languageCodes as $languageCode) {
                $translation = $this->createTranslationObject(
                    $anr,
                    TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
                    $scaleType->getLabelTranslationKey(),
                    $languageCode,
                    ''
                );
                $this->translationTable->save($translation, false);
            }
        }

        for ($index = $min; $index <= $max; $index++) {
            $this->createScaleComment(
                $anr,
                $scale,
                $scaleType ?? null,
                $index,
                $index,
                $languageCodes
            );
        }

        $this->operationalRiskScaleTable->flush();
    }

    public function createOperationalRiskScaleType(AnrSuperClass $anr, array $data): int
    {
        $languages = [];
        $operationalRiskScale = $this->operationalRiskScaleTable->findByAnrAndScaleId($anr, (int)$data['scaleId']);

        $operationalRiskScaleType = $this->createOperationalRiskScaleTypeObject($anr, $operationalRiskScale);

        // Create a translations for the scale.
        if (\is_array($data['label'])) {
            foreach ($data['label'] as $lang => $labelText) {
                $translation = $this->createTranslationObject(
                    $anr,
                    TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
                    $operationalRiskScaleType->getLabelTranslationKey(),
                    $lang,
                    (string)$labelText
                );
                $this->translationTable->save($translation, false);

                $languages[] = $lang;
            }
        } else {
            // FrontOffice scenario.
            $anrLanguageCode = $this->getLanguageCodeByAnr($anr);
            $translation = $this->createTranslationObject(
                $anr,
                TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE,
                $operationalRiskScaleType->getLabelTranslationKey(),
                $anrLanguageCode,
                $data['label'][$anrLanguageCode]
            );
            $this->translationTable->save($translation, false);

            $languages[] = $anrLanguageCode;
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
        foreach ($this->instanceRiskOpTable->findByAnr($anr) as $operationalInstanceRisk) {
            $operationalInstanceRiskScale = $this->instanceRiskOpService->createOperationalInstanceRiskScaleObject(
                $operationalInstanceRisk,
                $operationalRiskScaleType
            );
            $this->operationalInstanceRiskScaleTable->save($operationalInstanceRiskScale, false);
        }

        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType);

        return $operationalRiskScaleType->getId();
    }

    public function getOperationalRiskScales(AnrSuperClass $anr, ?string $language = null): array
    {
        $result = [];
        $operationalRiskScales = $this->operationalRiskScaleTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getLanguageCodeByAnr($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::OPERATIONAL_RISK_SCALE_TYPE, TranslationSuperClass::OPERATIONAL_RISK_SCALE_COMMENT],
            $language
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $comments = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                if ($operationalRiskScaleComment->isHidden()
                    || $operationalRiskScaleComment->getOperationalRiskScaleType() !== null) {
                    continue;
                }
                $translationComment = $translations[$operationalRiskScaleComment->getCommentTranslationKey()] ?? null;
                $comments[] = [
                    'id' => $operationalRiskScaleComment->getId(),
                    'scaleId' => $operationalRiskScale->getId(),
                    'scaleTypeId' => null,
                    'scaleIndex' => $operationalRiskScaleComment->getScaleIndex(),
                    'scaleValue' => $operationalRiskScaleComment->getScaleValue(),
                    'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                ];
            }

            $types = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleTypes() as $operationalRiskScaleType) {
                $commentsOfType = [];
                foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $commentOfType) {
                    if ($commentOfType->isHidden()) {
                        continue;
                    }
                    $translationComment = $translations[$commentOfType->getCommentTranslationKey()] ?? null;
                    $commentsOfType[] = [
                        'id' => $commentOfType->getId(),
                        'scaleId' => $operationalRiskScale->getId(),
                        'scaleTypeId' => $operationalRiskScaleType->getId(),
                        'scaleIndex' => $commentOfType->getScaleIndex(),
                        'scaleValue' => $commentOfType->getScaleValue(),
                        'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                    ];
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

    public function deleteOperationalRiskScaleTypes(array $data): void
    {
        $translationsKeys = [];

        foreach ($data as $id) {
            /** @var OperationalRiskScaleType $scaleTypeToDelete */
            $scaleTypeToDelete = $this->operationalRiskScaleTypeTable->findById($id);
            $translationsKeys[] = $scaleTypeToDelete->getLabelTranslationKey();

            foreach ($scaleTypeToDelete->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                $translationsKeys[] = $operationalRiskScaleComment->getCommentTranslationKey();
            }

            $this->operationalRiskScaleTable->remove($scaleTypeToDelete, false);

        }
        $this->operationalRiskScaleTable->flush();

        if (!empty($translationsKeys)) {
            $this->translationTable->deleteListByKeys($translationsKeys);
        }
    }

    public function update(int $id, array $data): int
    {
        /** @var OperationalRiskScaleTypeSuperClass $operationalRiskScaleType */
        $operationalRiskScaleType = $this->operationalRiskScaleTypeTable->findById($id);

        $scaleTypeVisibilityBeforeUpdate = $operationalRiskScaleType->isHidden();
        $operationalRiskScaleType->setIsHidden(!empty($data['isHidden']));

        if (!empty($data['label'])) {
            $languageCode = $data['language'] ?? $this->getLanguageCodeByAnr($operationalRiskScaleType->getAnr());
            $translationKey = $operationalRiskScaleType->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($operationalRiskScaleType->getAnr(), $translationKey, $languageCode);
                $translation->setValue($data['label']);
                $this->translationTable->save($translation, false);
            }
        }
        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType);

        /* If the scale type visibility is changed, it's necessary to recalculate the total risk's values. */
        if ($scaleTypeVisibilityBeforeUpdate !== $operationalRiskScaleType->isHidden()) {
            $updatedInstanceRiskIds = [];
            foreach ($operationalRiskScaleType->getOperationalInstanceRiskScales() as $operationalInstanceRiskScale) {
                $operationalInstanceRisk = $operationalInstanceRiskScale->getOperationalInstanceRisk();
                if (!\in_array($operationalInstanceRisk->getId(), $updatedInstanceRiskIds, true)) {
                    $this->instanceRiskOpService->updateRiskCacheValues($operationalInstanceRisk);
                    $updatedInstanceRiskIds[] = $operationalInstanceRisk->getId();
                }
            }
            $this->operationalRiskScaleTypeTable->flush();
        }

        return $operationalRiskScaleType->getId();
    }

    public function updateValueForAllScales(AnrSuperClass $anr, array $data): void
    {
        $scaleIndex = (int)$data['scaleIndex'];
        $scaleValue = (int)$data['scaleValue'];

        $operationalRiskScaleComments = $this->operationalRiskScaleCommentTable->findByAnrAndScaleTypeOrderByIndex(
            $anr,
            OperationalRiskScaleSuperClass::TYPE_IMPACT
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

    public function updateLevelsNumberOfOperationalRiskScale(AnrSuperClass $anr, array $data)
    {
        $languageCodes = $this->getLanguageCodesForTranslations();

        // Change the levels number of the scale.
        $levelsNumber = (int)$data['numberOfLevelForOperationalImpact'];

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            OperationalRiskScaleSuperClass::TYPE_IMPACT
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

    public function updateMinMaxForOperationalRiskProbability(AnrSuperClass $anr, $data): void
    {
        $probabilityMin = (int)$data['probabilityMin'];
        $probabilityMax = (int)$data['probabilityMax'];

        $languageCodes = $this->getLanguageCodesForTranslations();

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            OperationalRiskScaleSuperClass::TYPE_LIKELIHOOD
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

    protected function createOperationalRiskScaleTypeObject(
        AnrSuperClass $anr,
        OperationalRiskScaleSuperClass $operationalRiskScale
    ): OperationalRiskScaleTypeSuperClass {
        return (new OperationalRiskScaleType())
            ->setAnr($anr)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setCreator($this->connectedUser->getEmail());
    }

    protected function createTranslationObject(
        AnrSuperClass $anr,
        string $type,
        string $key,
        string $lang,
        string $value
    ): TranslationSuperClass {
        return (new Translation())
            ->setAnr($anr)
            ->setType($type)
            ->setKey($key)
            ->setLang($lang)
            ->setValue($value)
            ->setCreator($this->connectedUser->getEmail());
    }

    protected function createScaleComment(
        AnrSuperClass $anr,
        OperationalRiskScaleSuperClass $operationalRiskScale,
        ?OperationalRiskScaleTypeSuperClass $operationalRiskScaleType,
        int $scaleIndex,
        int $scaleValue,
        array $languageCodes
    ): void {
        $scaleComment = (new OperationalRiskScaleComment())
            ->setAnr($anr)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setScaleIndex($scaleIndex)
            ->setScaleValue($scaleValue)
            ->setCommentTranslationKey((string)Uuid::uuid4())
            ->setCreator($this->connectedUser->getEmail());
        if ($operationalRiskScaleType !== null) {
            $scaleComment->setOperationalRiskScaleType($operationalRiskScaleType);
        }

        $this->operationalRiskScaleCommentTable->save($scaleComment, false);

        foreach ($languageCodes as $languageCode) {
            // Create a translation for the scaleComment (init with blank value).
            $translation = $this->createTranslationObject(
                $anr,
                TranslationSuperClass::OPERATIONAL_RISK_SCALE_COMMENT,
                $scaleComment->getCommentTranslationKey(),
                $languageCode,
                ''
            );

            $this->translationTable->save($translation, false);
        }
    }

    protected function getLanguageCodeByAnr(AnrSuperClass $anr): string
    {
        return throw new \LogicException('The "Core\Anr" class does not have language code.');
    }

    protected function getLanguageCodesForTranslations(): array
    {
        return $this->configService->getActiveLanguageCodes();
    }

    /**
     * @param int $index
     * @param OperationalRiskScaleCommentSuperClass[] $operationalRiskScaleComments
     *
     * @return OperationalRiskScaleCommentSuperClass|null
     */
    private function getCommentByIndex(
        int $index,
        iterable $operationalRiskScaleComments
    ): ?OperationalRiskScaleCommentSuperClass {
        foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
            if ($operationalRiskScaleComment->getScaleIndex() === $index) {
                return $operationalRiskScaleComment;
            }
        }

        return null;
    }
}
