<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Table\SoaScaleCommentTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class SoaScaleCommentService
{
    private SoaScaleCommentTable $soaScaleCommentTable;

    private TranslationTable $translationTable;

    private ConfigService $configService;

    private UserSuperClass $connectedUser;

    public function __construct(
        SoaScaleCommentTable $soaScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getSoaScaleComments(Anr $anr, string $language): array
    {
        $result = [];
        /** @var SoaScaleComment[] $soaScaleComments */
        $soaScaleComments = $this->soaScaleCommentTable->findByAnrOrderByIndex($anr);

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::SOA_SCALE_COMMENT],
            $language
        );

        foreach ($soaScaleComments as $comment) {
            $translationComment = $translations[$comment->getLabelTranslationKey()] ?? null;
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

    public function createOrHideSoaScaleComments(Anr $anr, array $data): void
    {
        $soaScaleComments = $this->soaScaleCommentTable->findByAnrOrderByIndex($anr);

        if (isset($data['numberOfLevels'])) {
            $levelsNumber = (int)$data['numberOfLevels'];
            foreach ($soaScaleComments as $soaScaleComment) {
                $soaScaleComment
                    ->setIsHidden($soaScaleComment->getScaleIndex() >= $levelsNumber)
                    ->setUpdater($this->connectedUser->getEmail());
                $this->soaScaleCommentTable->save($soaScaleComment, false);
            }
            $numberOfCurrentComments = \count($soaScaleComments);
            if ($levelsNumber > $numberOfCurrentComments) {
                $languageCodes = $this->configService->getActiveLanguageCodes();
                for ($i = $numberOfCurrentComments; $i < $levelsNumber; $i++) {
                    $this->createSoaScaleComment($anr, $i, $languageCodes);
                }
            }

            $this->soaScaleCommentTable->flush();
        }
    }

    public function update(Anr $anr, int $id, array $data): void
    {
        /** @var SoaScaleComment $soaScaleComment */
        $soaScaleComment = $this->soaScaleCommentTable->findByIdAndAnr($id, $anr);

        if (isset($data['comment'])) {
            $languageCode = $data['language'];
            $translationKey = $soaScaleComment->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable->findByAnrKeyAndLanguage($anr, $translationKey, $languageCode);
                $translation->setValue($data['comment'])->setUpdater($this->connectedUser->getEmail());
                $this->translationTable->save($translation, false);
            }
        }
        if (!empty($data['colour'])) {
            $soaScaleComment->setColour($data['colour'])->setUpdater($this->connectedUser->getEmail());
        }

        $this->soaScaleCommentTable->save($soaScaleComment);
    }

    protected function createSoaScaleComment(Anr $anr, int $scaleIndex, array $languageCodes): void
    {
        $scaleComment = (new SoaScaleComment())
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setAnr($anr)
            ->setScaleIndex($scaleIndex)
            ->setColour('')
            ->setIsHidden(false)
            ->setCreator($this->connectedUser->getEmail());

        $this->soaScaleCommentTable->save($scaleComment, false);

        foreach ($languageCodes as $languageCode) {
            // Create a translation for the scaleComment (init with blank value).
            $this->createTranslationObject(
                $anr,
                TranslationSuperClass::SOA_SCALE_COMMENT,
                $scaleComment->getLabelTranslationKey(),
                $languageCode,
                ''
            );
        }
    }

    protected function createTranslationObject(
        Anr $anr,
        string $type,
        string $key,
        string $lang,
        string $value
    ): Translation {
        $translation = (new Translation())
            ->setAnr($anr)
            ->setType($type)
            ->setKey($key)
            ->setLang($lang)
            ->setValue($value)
            ->setCreator($this->connectedUser->getEmail());

        $this->translationTable->save($translation, false);

        /** @var Translation */
        return $translation;
    }
}
