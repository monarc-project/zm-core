<?php declare(strict_types=1);

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Model\Table\OperationalRiskScaleTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class OperationalRiskScaleService
{
    private AnrTable $anrTable;

    private UserSuperClass $connectedUser;

    private OperationalRiskScaleTable $operationalRiskScaleTable;

    private OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    private TranslationTable $translationTable;

    private ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        ConnectedUserService $connectedUserService,
        OperationalRiskScaleTable $operationalRiskScaleTable,
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createOperationalRiskScale(int $anrId, array $data): int
    {
        $anr = $this->anrTable->findById($anrId);
        $languages = [];

        $operationalRiskScale = (new OperationalRiskScale())
            ->setAnr($anr)
            ->setType($data['type'])
            ->setMin($data['min'])
            ->setMax($data['max'])
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setCreator($this->connectedUser->getEmail());

        // Create a translation for the scale.
        foreach ($data['Label'] as $key => $label) {
            $translation = (new Translation())
                ->setCreator($this->connectedUser->getEmail())
                ->setType(OperationalRiskScale::class)
                ->setKey($operationalRiskScale->getLabelTranslationKey())
                ->setLang($key)
                ->setValue($label != null ? $label: '');

            $this->translationTable->save($translation, false);
            $languages[] = $key;
        }


        // Process the scale comments.
        if (!empty($data['comments'])) {
            foreach ($data['comments'] as $scaleCommentData) {
                $scaleComment = (new OperationalRiskScaleComment())
                    ->setCreator($this->connectedUser->getEmail())
                    ->setAnr($anr)
                    ->setScaleIndex($scaleCommentData['scaleIndex'])
                    ->setScaleValue($scaleCommentData['scaleValue'])
                    ->setCommentTranslationKey((string)Uuid::uuid4())
                    ->setOperationalRiskScale($operationalRiskScale);

                $this->operationalRiskScaleCommentTable->save($scaleComment, false);

                // Create a translation for the scaleComment (init with blank value).
                foreach ($languages as $language) {
                    $translation = (new Translation())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setType(OperationalRiskScaleComment::class)
                        ->setKey($scaleComment->getCommentTranslationKey())
                        ->setLang($language)
                        ->setValue('');

                    $this->translationTable->save($translation, false);
                }
            }
        }

        $this->operationalRiskScaleTable->save($operationalRiskScale);

        return $operationalRiskScale->getId();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getOperationalRiskScales(int $anrId, string $language=null): array
    {
        $anr = $this->anrTable->findById($anrId);
        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnr($anr);
        $result = [];

        if($language==null)
          $language=strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);

        $translations = $this->translationTable->findByTypesAndLanguageIndexedByKey(
            [OperationalRiskScale::class, OperationalRiskScaleComment::class],
            $language
        );

        foreach ($operationalRiskScales as $operationalRiskScale) {
            $comments = [];
            foreach ($operationalRiskScale->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                $translationComment = $translations[$operationalRiskScaleComment->getCommentTranslationKey()] ?? null;
                $comments[] = [
                    'id' => $operationalRiskScaleComment->getId(),
                    'scaleId' => $operationalRiskScale->getId(),
                    'scaleIndex' => $operationalRiskScaleComment->getScaleIndex(),
                    'scaleValue' => $operationalRiskScaleComment->getScaleValue(),
                    'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                ];
            }

            usort($comments, static function ($a, $b) {
                return $a['scaleIndex'] <=> $b['scaleIndex'];
            });

            $comments = \array_slice(
                $comments,
                $operationalRiskScale->getMin(),
                $operationalRiskScale->getMax() - $operationalRiskScale->getMin() + 1
            );

            $translationLabel = '';
            if (!empty($operationalRiskScale->getLabelTranslationKey())) {
                $translationScale = $translations[$operationalRiskScale->getLabelTranslationKey()] ?? null;
                $translationLabel = $translationScale ? $translationScale->getValue() : '';
            }

            $result[] = [
                'id' => $operationalRiskScale->getId(),
                'max' => $operationalRiskScale->getMax(),
                'min' => $operationalRiskScale->getMin(),
                'type' => $operationalRiskScale->getType(),
                'isHidden' => $operationalRiskScale->isHidden(),
                'label' => $translationLabel,
                'comments' => $comments,
            ];
        }

        return $result;
    }

    public function deleteOperationalRiskScales(array $data): void
    {
        $translationsKeys = [];

        foreach ($data as $id) {
            /** @var OperationalRiskScale $scaleToDelete */
            $scaleToDelete = $this->operationalRiskScaleTable->findById($id);
            if ($scaleToDelete === null) {
                throw new EntityNotFoundException(sprintf('Scale with ID %d is not found', $id));
            }
            $translationsKeys[] = $scaleToDelete->getLabelTranslationKey();

            foreach ($scaleToDelete->getOperationalRiskScaleComments() as $operationalRiskScaleComment) {
                $translationsKeys[] = $operationalRiskScaleComment->getCommentTranslationKey();
            }

            $this->operationalRiskScaleTable->remove($scaleToDelete, true);

        }

        if (!empty($translationsKeys)) {
            $this->translationTable->deleteListByKeys($translationsKeys);
        }
    }

    public function update(int $id, array $data): int
    {
        $anr = $this->anrTable->findById($data['anr']);

        /** @var OperationalRiskScale $operationalRiskScale */
        $operationalRiskScale = $this->operationalRiskScaleTable->findById($id);

        $operationalRiskScale->setIsHidden(!empty($data['isHidden']));

        if (!empty($data['label']) && !empty($data['language'])) {
            $translationKey = $operationalRiskScale->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByKeyAndLanguage($translationKey, $data['language']);
                $translation->setValue($data['label']);
                $this->translationTable->save($translation, false);
            }
        }
        $this->operationalRiskScaleTable->save($operationalRiskScale);

        return $operationalRiskScale->getId();
    }

    public function updateValueForAllScale(array $data): void
    {
        $anr = $this->anrTable->findById($data['anr']);
        $scaleIndex = (int)$data['scaleIndex'];

        // Update the value for all the scales.
        $scaleValue = (int)$data['scaleValue'];
        $nextCommentValue = $scaleValue;

        $operationalRiskScaleComments = $this->operationalRiskScaleCommentTable
            ->findAllByAnrAndIndexAndScaleType($anr, $scaleIndex, 1);

        foreach ($operationalRiskScaleComments as $operationalRiskScaleComment) {
            $operationalRiskScaleComment->setScaleValue($scaleValue);
            $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment);
        }

        $nextOperationalRiskScaleComments = $this->operationalRiskScaleCommentTable
            ->findNextCommentsToUpdateByAnrAndIndexAndType($anr, $scaleIndex, 1);

        foreach ($nextOperationalRiskScaleComments as $nextOperationalRiskScaleComment) {
            $nextCommentValue++;
            $nextOperationalRiskScaleComment->setScaleValue($nextCommentValue);
            $this->operationalRiskScaleCommentTable->save($nextOperationalRiskScaleComment, false);
        }
        $this->operationalRiskScaleCommentTable->flush();
    }

    public function updateNumberOfLevelForOperationalRiskScale(array $data)
    {
        $anr = $this->anrTable->findById($data['anr']);
        $anrLanguageCode = strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);

        // Change the number of level in the scale.
        $max = (int)$data['numberOfLevelForOperationalImpact'];

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType($anr, 1);

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
                    $scaleComment = (new OperationalRiskScaleComment())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setAnr($anr)
                        ->setScaleIndex($i)
                        ->setScaleValue($maxScaleValue)
                        ->setCommentTranslationKey((string)Uuid::uuid4())
                        ->setOperationalRiskScale($operationalRiskScale);

                    $this->operationalRiskScaleCommentTable->save($scaleComment, false);

                    // Create a translation for the scaleComment (init with blank value).
                    $translation = (new Translation())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setType(OperationalRiskScaleComment::class)
                        ->setKey($scaleComment->getCommentTranslationKey())
                        ->setLang($anrLanguageCode)
                        ->setValue('');

                    $this->translationTable->save($translation, false);
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
        $anrLanguageCode = strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);

        $operationalRiskScales = $this->operationalRiskScaleTable->findWithCommentsByAnrAndType($anr, 2);

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

            if ($probabilityMin < $actualMin) {
                for ($i = $probabilityMin; $i < $actualMin; $i++) {
                    $scaleComment = (new OperationalRiskScaleComment())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setAnr($anr)
                        ->setScaleIndex($i)
                        ->setScaleValue($i)
                        ->setCommentTranslationKey((string)Uuid::uuid4())
                        ->setOperationalRiskScale($operationalRiskScale);

                    $this->operationalRiskScaleCommentTable->save($scaleComment, false);

                    // Create a translation for the scaleComment (init with blank value).
                    $translation = (new Translation())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setType(OperationalRiskScaleComment::class)
                        ->setKey($scaleComment->getCommentTranslationKey())
                        ->setLang($anrLanguageCode)
                        ->setValue('');

                    $this->translationTable->save($translation, false);
                }
            }
            if ($probabilityMax > $actualMax) {
                for ($i = $actualMax + 1; $i <= $probabilityMax; $i++) {
                    $scaleComment = (new OperationalRiskScaleComment())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setAnr($anr)
                        ->setScaleIndex($i)
                        ->setScaleValue($i)
                        ->setCommentTranslationKey((string)Uuid::uuid4())
                        ->setOperationalRiskScale($operationalRiskScale);

                    $this->operationalRiskScaleCommentTable->save($scaleComment, false);

                    // Create a translation for the scaleComment (init with blank value).
                    $translation = (new Translation())
                        ->setCreator($this->connectedUser->getEmail())
                        ->setType(OperationalRiskScaleComment::class)
                        ->setKey($scaleComment->getCommentTranslationKey())
                        ->setLang($anrLanguageCode)
                        ->setValue('');

                    $this->translationTable->save($translation, false);
                }
            }
            $operationalRiskScale->setMin($probabilityMin);
            $operationalRiskScale->setMax($probabilityMax);

            $this->operationalRiskScaleTable->save($operationalRiskScale);
        }
    }
}
