<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Table\SoaScaleCommentTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class SoaScaleCommentService
{
    protected UserSuperClass $connectedUser;

    protected SoaScaleCommentTable $soaScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        ConnectedUserService $connectedUserService,
        SoaScaleCommentTable $soaScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function getSoaScaleComments(Anr $anr, string $language = null): array
    {
        $result = [];
        $soaScaleComments = $this->soaScaleCommentTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::SOA_SCALE_COMMENT],
            $language
        );

        foreach ($soaScaleComments as $comment) {
            $translationComment = $translations[$comment->getCommentTranslationKey()] ?? null;
            $result[] = [
                'id' => $comment->getId(),
                'scaleIndex' => $comment->getScaleIndex(),
                'colour' => $comment->getColour(),
                'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                'isHidden' => $comment->isHidden(),
            ];
        }

        return $result;
    }

    public function createOrHideSoaScaleComment(Anr $anr, array $data): void
    {
        $soaScaleComments = $this->soaScaleCommentTable->findByAnr($anr);
        $numberOfCurrentComments = \count($soaScaleComments);

        if (isset($data['numberOfLevels'])) {
            $levelsNumber = (int)$data['numberOfLevels'];
            foreach ($soaScaleComments as $soaScaleComment) {
                if ($soaScaleComment->getScaleIndex() < $levelsNumber) {
                    $soaScaleComment->setIsHidden(false);
                } else {
                    $soaScaleComment->setIsHidden(true);
                }
                $this->soaScaleCommentTable->save($soaScaleComment, false);
            }
            if ($levelsNumber > $numberOfCurrentComments) {
                $languageCodes = $this->getLanguageCodesForTranslations($anr);
                for ($i=$numberOfCurrentComments; $i < $levelsNumber; $i++) {
                    $this->createSoaScaleComment(
                        $anr,
                        $i,
                        $languageCodes
                    );
                }
            }

            $this->soaScaleCommentTable->flush();
        }
    }

    public function update(int $id, array $data): void
    {
        /** @var SoaScaleComment $soaScaleComment */
        $soaScaleComment = $this->soaScaleCommentTable->findById($id);

        if (!empty($data['comment'])) {
            $languageCode = $data['language'] ?? $this->getAnrLanguageCode($soaScaleComment->getAnr());
            $translationKey = $soaScaleComment->getCommentTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($soaScaleComment->getAnr(), $translationKey, $languageCode);
                $translation->setValue($data['comment']);
                $this->translationTable->save($translation, false);
            }
        }
        if (!empty($data['colour'])) {
            $soaScaleComment->setColour($data['colour']);
        }

        $this->soaScaleCommentTable->save($soaScaleComment);
    }

    public function getSoaScaleCommentsDataById(Anr $anr, string $language = null): array
    {
        $result = [];
        $soaScaleComments = $this->soaScaleCommentTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::SOA_SCALE_COMMENT],
            $language
        );

        foreach ($soaScaleComments as $comment) {
            $translationComment = $translations[$comment->getCommentTranslationKey()] ?? null;
            $result[$comment->getId()] = [
               'id' => $comment->getId(),
               'scaleIndex' => $comment->getScaleIndex(),
               'colour' => $comment->getColour(),
               'comment' => $translationComment !== null ? $translationComment->getValue() : '',
               'isHidden' => $comment->isHidden(),
            ];
        }

        return $result;
    }

    protected function createSoaScaleComment(
        AnrSuperClass $anr,
        int $scaleIndex,
        array $languageCodes,
        bool $isFlushable = false
    ): void {
        $scaleComment = (new soaScaleComment())
            ->setAnr($anr)
            ->setScaleIndex($scaleIndex)
            ->setColour('')
            ->setIsHidden(false)
            ->setCommentTranslationKey((string)Uuid::uuid4())
            ->setCreator($this->connectedUser->getEmail());

        $this->soaScaleCommentTable->save($scaleComment, false);

        foreach ($languageCodes as $languageCode) {
            // Create a translation for the scaleComment (init with blank value).
            $translation = $this->createTranslationObject(
                $anr,
                TranslationSuperClass::SOA_SCALE_COMMENT,
                $scaleComment->getCommentTranslationKey(),
                $languageCode,
                ''
            );

            $this->translationTable->save($translation, false);
        }

        if ($isFlushable) {
            $this->soaScaleCommentTable->flush();
        }
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

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return $this->configService->getActiveLanguageCodes()[$anr->getLanguage()];
    }

    protected function getLanguageCodesForTranslations(AnrSuperClass $anr): array
    {
        return $this->configService->getActiveLanguageCodes();
    }
}
