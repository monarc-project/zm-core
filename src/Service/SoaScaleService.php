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

class SoaScaleService
{
    protected AnrTable $anrTable;

    protected UserSuperClass $connectedUser;

    protected SoaScaleTable $soaScaleTable;

    protected SoaScaleCommentTable $soaScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        ConnectedUserService $connectedUserService,
        SoaScaleTable $soaScaleTable,
        SoaScaleCommentTable $soaScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->soaScaleTable = $soaScaleTable;
        $this->soaScaleCommentTable = $soaScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function createScale(
        AnrSuperClass $anr,
        int $numberOfLevels
    ): void {
        $scale = (new SoaRiskScale())
            ->setAnr($anr)
            ->setNumberOfLevels($numberOfLevels)
            ->setCreator($this->connectedUser->getEmail());

        $this->soaScaleTable->save($scale);

        $languageCodes = $this->getLanguageCodesForTranslations($anr);

        if ($type === OperationalRiskScale::TYPE_IMPACT) {
            $scaleType = $this->createOperationalRiskScaleTypeObject($anr, $scale);
            foreach ($languageCodes as $languageCode) {
                $translation = $this->createTranslationObject(
                    $anr,
                    Translation::OPERATIONAL_RISK_SCALE_TYPE,
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
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getSoaScale(int $anrId, string $language = null): array
    {
        $result = [];
        $anr = $this->anrTable->findById($anrId);
        $soaScale = $this->soaScaleTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::SOA_SCALE_COMMENT],
            $language
        );

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
