<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\SoaScaleCommentSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\SoaScaleCommentTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class SoaScaleCommentExportService
{
    protected SoaScaleCommentTable $soaScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        SoaScaleCommentTable $soaScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function generateExportArray(AnrSuperClass $anr): array
    {
        $result = [];

        // TODO: we need to fetch the translations without language code for BO and handle it differently later on.
        $soaScaleCommentTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::SOA_SCALE_COMMENT],
            $this->getAnrLanguageCode($anr)
        );

        /** @var SoaScaleCommentTable $soaScaleCommentTable */
        $scales = $this->soaScaleCommentTable->findByAnr($anr);
        foreach ($scales as $scale) {
            if (!$scale->isHidden()) {
                $translationComment = $soaScaleCommentTranslations[$scale->getCommentTranslationKey()] ?? null;
                $result[$scale->getId()] = [
                    'scaleIndex' => $scale->getScaleIndex(),
                    'isHidden' => $scale->isHidden(),
                    'colour' => $scale->getColour(),
                    'comment' => $translationComment !== null ? $translationComment->getValue() : '',
                ];
            }
        }
        return $result;
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return $this->configService->getActiveLanguageCodes()[$anr->getLanguage()];
    }
}
