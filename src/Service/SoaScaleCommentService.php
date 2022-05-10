<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\SoaScaleCommentSuperClass;
use Monarc\Core\Model\Entity\SoaScaleSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\SoaScale;
use Monarc\Core\Model\Entity\SoaScaleComment;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\SoaScaleCommentTable;
use Monarc\Core\Model\Table\SoaScaleTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class SoaScaleCommentService
{
    protected AnrTable $anrTable;

    protected UserSuperClass $connectedUser;

    protected SoaScaleCommentTable $soaScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        ConnectedUserService $connectedUserService,
        SoaScaleCommentTable $soaScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

     /**
     * @throws EntityNotFoundException
     */
    public function getSoaScaleComments(int $anrId, string $language = null): array
    {
        $result = [];
        $anr = $this->anrTable->findById($anrId);
        $soaScaleComments = $this->soaScaleCommentTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::SOA_SCALE_COMMENT],
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
