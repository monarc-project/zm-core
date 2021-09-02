<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleCommentSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleTypeSuperClass;
use Monarc\Core\Model\Table\OperationalRiskScaleTable;
use Monarc\Core\Model\Table\TranslationTable;

class OperationalRiskScalesExportService
{
    private OperationalRiskScaleTable $operationalRiskScaleTable;
    private TranslationTable $translationTable;
    private ConfigService $configService;

    public function __construct(
        OperationalRiskScaleTable $operationalRiskScaleTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->operationalRiskScaleTable = $operationalRiskScaleTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function generateExportArray(AnrSuperClass $anr): array
    {
        $result = [];

        // TODO: we need to fetch the translations without language code for BO and handle it differently later on.
        $operationalRisksAndScalesTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [
                OperationalRiskScaleTypeSuperClass::TRANSLATION_TYPE_NAME,
                OperationalRiskScaleCommentSuperClass::TRANSLATION_TYPE_NAME
            ],
            $this->getAnrLanguageCode($anr)
        );

        $operationalRiskScales = $this->operationalRiskScaleTable->findByAnr($anr);
        foreach ($operationalRiskScales as $scale) {
            $scaleTypes = [];
            foreach ($scale->getOperationalRiskScaleTypes() as $scaleType) {
                $scaleTypeComments = [];
                foreach ($scaleType->getOperationalRiskScaleComments() as $scaleTypeComment) {
                    $scaleTypeComments[] = $this->getOperationalRiskScaleCommentData(
                        $scaleTypeComment,
                        $operationalRisksAndScalesTranslations
                    );
                }

                $typeTranslation = $operationalRisksAndScalesTranslations[$scaleType->getLabelTranslationKey()];
                $scaleTypes[] = [
                    'id' => $scaleType->getId(),
                    'isHidden' => $scaleType->isHidden(),
                    'labelTranslationKey' => $scaleType->getLabelTranslationKey(),
                    'translation' => [
                        'key' => $typeTranslation->getKey(),
                        'lang' => $typeTranslation->getLang(),
                        'value' => $typeTranslation->getValue(),
                    ],
                    'operationalRiskScaleComments' => $scaleTypeComments,
                ];
            }

            $scaleComments = [];
            foreach ($scale->getOperationalRiskScaleComments() as $scaleComment) {
                if ($scaleComment->getOperationalRiskScaleType() !== null) {
                    continue;
                }

                $scaleComments[] = $this->getOperationalRiskScaleCommentData(
                    $scaleComment,
                    $operationalRisksAndScalesTranslations
                );
            }

            $result[$scale->getType()] = [
                'id' => $scale->getId(),
                'min' => $scale->getMin(),
                'max' => $scale->getMax(),
                'type' => $scale->getType(),
                'operationalRiskScaleTypes' => $scaleTypes,
                'operationalRiskScaleComments' => $scaleComments,
            ];
        }

        return $result;
    }

    protected function getOperationalRiskScaleCommentData(
        OperationalRiskScaleCommentSuperClass $scaleComment,
        array $operationalRisksAndScalesTranslations
    ): array {
        $commentTranslation = $operationalRisksAndScalesTranslations[$scaleComment->getCommentTranslationKey()];

        return [
            'id' => $scaleComment->getId(),
            'scaleIndex' => $scaleComment->getScaleIndex(),
            'scaleValue' => $scaleComment->getScaleValue(),
            'isHidden' => $scaleComment->isHidden(),
            'commentTranslationKey' => $scaleComment->getCommentTranslationKey(),
            'translation' => [
                'key' => $commentTranslation->getKey(),
                'lang' => $commentTranslation->getLang(),
                'value' => $commentTranslation->getValue(),
            ],
        ];
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return strtolower($this->configService->getLanguageCodes()[$anr->getLanguage()]);
    }
}
