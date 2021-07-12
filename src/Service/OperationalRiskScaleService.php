<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Monarc\Core\Model\Entity\OperationalRiskScaleTypeSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTypeTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class OperationalRiskScaleService
{
    protected AnrTable $anrTable;

    protected UserSuperClass $connectedUser;

    protected OperationalRiskScaleTable $operationalRiskScaleTable;

    protected OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable;

    protected OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        ConnectedUserService $connectedUserService,
        OperationalRiskScaleTable $operationalRiskScaleTable,
        OperationalRiskScaleTypeTable $operationalRiskScaleTypeTable,
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleTypeTable = $operationalRiskScaleTypeTable;
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    /**
     * @param int $anrId
     * @param array $data
     *
     * @return int
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createOperationalRiskScaleType(int $anrId, array $data): int
    {
        $anr = $this->anrTable->findById($anrId);
        $languages = [];
        $operationalRiskScale = $this->operationalRiskScaleTable->findByAnrAndScaleId($anr, $data['scaleId']);

        $operationalRiskScaleType = $this->createOperationalRiskScaleTypeObject($anr, $operationalRiskScale);

        // Create a translations for the scale.
        if (\is_array($data['label'])) {
            foreach ($data['label'] as $lang => $labelText) {
                $translation = $this->createTranslationObject(
                    OperationalRiskScaleType::class,
                    $operationalRiskScaleType->getLabelTranslationKey(),
                    $lang,
                    (string)$labelText
                );
                $this->translationTable->save($translation, false);

                $languages[] = $lang;
            }
        } else {
            // Scenario for the frontOffice.
            $anrLanguageCode = $this->getAnrLanguageCode($anr);
            $translation = $this->createTranslationObject(
                OperationalRiskScaleType::class,
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

        $this->operationalRiskScaleTypeTable->save($operationalRiskScale);

        return $operationalRiskScaleType->getId();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getOperationalRiskScales(int $anrId, string $language = null): array
    {
        $result = [];
        $anr = $this->anrTable->findById($anrId);
        $operationalRiskScales = $this->operationalRiskScaleTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [OperationalRiskScaleType::TRANSLATION_TYPE_NAME, OperationalRiskScaleComment::TRANSLATION_TYPE_NAME],
            $language
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $comments = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                if ($operationalRiskScaleComment->getOperationalRiskScaleType() !== null) {
                    continue;
                }
                $translationComment = $translations[$operationalRiskScaleComment->getCommentTranslationKey()] ?? null;
                $comments[] = [
                    'id' => $operationalRiskScaleComment->getId(),
                    'scaleId' => $operationalRiskScale->getId(),
                    'scaleIndex' => $operationalRiskScaleComment->getScaleIndex(),
                    'scaleValue' => $operationalRiskScaleComment->getScaleValue(),
                    'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                ];
                if (\count($comments) === $operationalRiskScale->getMax()) {
                    break;
                }
            }
            usort($comments, static function ($a, $b) {
                return $a['scaleIndex'] <=> $b['scaleIndex'];
            });

            $types = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleTypes() as $operationalRiskScaleType) {
                $commentsOfType = [];
                foreach ($operationalRiskScaleType->getOperationalRiskScaleComments() as $commentOfType) {
                    $translationComment = $translations[$commentOfType->getCommentTranslationKey()] ?? null;
                    $commentsOfType[] = [
                        'id' => $commentOfType->getId(),
                        'scaleId' => $operationalRiskScale->getId(),
                        'scaleIndex' => $commentOfType->getScaleIndex(),
                        'scaleValue' => $commentOfType->getScaleValue(),
                        'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                    ];
                }

                usort($commentsOfType, static function ($a, $b) {
                return $a['scaleIndex'] <=> $b['scaleIndex'];
            });

                $translationLabel = $translations[$operationalRiskScaleType->getLabelTranslationKey()] ?? null;
                $types[] = [
                    'id' => $operationalRiskScaleType->getId(),
                    'scaleId' => $operationalRiskScale->getId(),
                    'label' => $translationLabel->getValue(),
                    'isHidden' => $operationalRiskScaleType->isHidden(),
                    'isSystem' => $operationalRiskScaleType->getIsSystem(),
                    'comments' => $commentsOfType,
                ];
            }

            $result[$operationalRiskScale->getType()] = [
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
            if ($scaleTypeToDelete === null) {
                throw new EntityNotFoundException(sprintf('Scale type with ID %d is not found', $id));
            }
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
        /** @var OperationalRiskScaleType $operationalRiskScaleType */
        $operationalRiskScaleType = $this->operationalRiskScaleTypeTable->findById($id);

        $operationalRiskScaleType->setIsHidden(!empty($data['isHidden']));

        if (!empty($data['label'])) {
            $languageCode = $data['language'] ?? $this->getAnrLanguageCode($operationalRiskScaleType->getAnr());
            $translationKey = $operationalRiskScaleType->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($operationalRiskScaleType->getAnr(), $translationKey, $languageCode);
                $translation->setValue($data['label']);
                $this->translationTable->save($translation, false);
            }
        }
        $this->operationalRiskScaleTypeTable->save($operationalRiskScaleType);

        return $operationalRiskScaleType->getId();
    }

    public function updateValueForAllScales(array $data): void
    {
        $anr = $this->anrTable->findById($data['anr']);
        $scaleIndex = (int)$data['scaleIndex'];

        // Update the value for all the scales.
        $scaleValue = (int)$data['scaleValue'];
        $nextCommentValue = $scaleValue;

        $operationalRiskScaleComments = $this->operationalRiskScaleCommentTable
            ->findAllByAnrScaleIndexAndScaleType($anr, $scaleIndex, OperationalRiskScale::TYPE_IMPACT);

        foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
            $operationalRiskScaleComment->setScaleValue($scaleValue);
            $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment, false);
        }
        $this->operationalRiskScaleCommentTable->flush();

        $nextOperationalRiskScaleComments = $this->operationalRiskScaleCommentTable
            ->findNextCommentsToUpdateByAnrAndIndexAndType($anr, $scaleIndex, OperationalRiskScale::TYPE_IMPACT);

        foreach ($nextOperationalRiskScaleComments as $nextOperationalRiskScaleComment) {
            $nextCommentValue++;
            $nextOperationalRiskScaleComment->setScaleValue($nextCommentValue);
            $this->operationalRiskScaleCommentTable->save($nextOperationalRiskScaleComment, false);
        }
        $this->operationalRiskScaleCommentTable->flush();
    }

    public function updateLevelsNumberOfOperationalRiskScale(array $data)
    {
        $anr = $this->anrTable->findById($data['anr']);
        $anrLanguageCode = $this->getAnrLanguageCode($anr);

        // Change the levels number of the scale.
        $max = (int)$data['numberOfLevelForOperationalImpact'];

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            OperationalRiskScale::TYPE_IMPACT
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $maxScaleValue = 0;
            $operationalRiskScaleComments = $operationalRiskScale->getOperationalRiskScaleComments();
            $commentsSize = \count($operationalRiskScaleComments);

            foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
                if ($operationalRiskScaleComment->getScaleValue() > $maxScaleValue) {
                    $maxScaleValue = $operationalRiskScaleComment->getScaleValue();
                }
            }

            // Create new operationalScaleComment.
            if ($max > $commentsSize) {
                for ($i = $commentsSize; $i < $max; $i++) {
                    $maxScaleValue++;
                    $this->createScaleComment($anr, $operationalRiskScale, $i, $maxScaleValue, [$anrLanguageCode]);
                }
            }

            // Set -1, because in the range we count from 0.
            $operationalRiskScale->setMax($max - 1);

            $this->operationalRiskScaleTable->save($operationalRiskScale);
        }
    }

    public function updateMinMaxForOperationalRiskProbability($data): void
    {
        $probabilityMin = (int)$data['probabilityMin'];
        $probabilityMax = (int)$data['probabilityMax'];

        $anr = $this->anrTable->findById($data['anr']);
        $anrLanguageCode = $this->getAnrLanguageCode($anr);

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType(
            $anr,
            OperationalRiskScale::TYPE_LIKELIHOOD
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $actualMin = 999; //init with a high value
            $actualMax = 0;
            $operationalRiskScaleComments = $operationalRiskScale->getOperationalRiskScaleComments();

            foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
                if ($actualMax <= $operationalRiskScaleComment->getScaleValue()) {
                    $actualMax = $operationalRiskScaleComment->getScaleValue();
                }
                if ($actualMin >= $operationalRiskScaleComment->getScaleValue()) {
                    $actualMin = $operationalRiskScaleComment->getScaleValue();
                }
            }

            // TODO: where is the removal ???

            if ($probabilityMin < $actualMin) {
                for ($i = $probabilityMin; $i < $actualMin; $i++) {
                    $this->createScaleComment($anr, $i, $i, $operationalRiskScale, [$anrLanguageCode]);
                }
            } elseif ($probabilityMax > $actualMax) {
                for ($i = $actualMax + 1; $i <= $probabilityMax; $i++) {
                    $this->createScaleComment($anr, $i, $i, $operationalRiskScale, [$anrLanguageCode]);
                }
            }
            $operationalRiskScale->setMin($probabilityMin);
            $operationalRiskScale->setMax($probabilityMax);

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
            ->setIsSystem(0)
            ->setCreator($this->connectedUser->getEmail());
    }

    protected function createTranslationObject(
        string $type,
        string $key,
        string $lang,
        string $value
    ): TranslationSuperClass {
        return (new Translation())
            ->setCreator($this->connectedUser->getEmail())
            ->setType($type)
            ->setKey($key)
            ->setLang($lang)
            ->setValue($value);
    }

    protected function createScaleComment(
        AnrSuperClass $anr,
        OperationalRiskScaleSuperClass $operationalRiskScale,
        OperationalRiskScaleTypeSuperClass $operationalRiskScaleType,
        int $scaleIndex,
        int $maxScaleValue,
        array $anrLanguageCodes
    ): void {
        $scaleComment = (new OperationalRiskScaleComment())
            ->setAnr($anr)
            ->setOperationalRiskScale($operationalRiskScale)
            ->setOperationalRiskScaleType($operationalRiskScaleType)
            ->setScaleIndex($scaleIndex)
            ->setScaleValue($maxScaleValue)
            ->setCommentTranslationKey((string)Uuid::uuid4())
            ->setCreator($this->connectedUser->getEmail());

        $this->operationalRiskScaleCommentTable->save($scaleComment, false);

        foreach ($anrLanguageCodes as $anrLanguageCode) {
            // Create a translation for the scaleComment (init with blank value).
            $translation = $this->createTranslationObject(
                OperationalRiskScaleComment::TRANSLATION_TYPE_NAME,
                $scaleComment->getCommentTranslationKey(),
                $anrLanguageCode,
                ''
            );

            $this->translationTable->save($translation, false);
        }
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);
    }
}
