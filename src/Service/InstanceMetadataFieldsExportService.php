<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceMetadataFieldSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Table\InstanceMetadataFieldTable;
use Monarc\Core\Table\TranslationTable;

class InstanceMetadataFieldsExportService
{
    protected InstanceMetadataFieldTable $instanceMetadataFieldTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        InstanceMetadataFieldTable $instanceMetadataFieldTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->instanceMetadataFieldTable = $instanceMetadataFieldTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function generateExportArray(AnrSuperClass $anr): array
    {
        $result = [];

        // TODO: we need to fetch the translations without language code for BO and handle it differently later on.
        $instanceMetadataFieldsTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::INSTANCE_METADATA],
            $this->getAnrLanguageCode($anr)
        );

        /** @var InstanceMetadataFieldSuperClass[] $instanceMetadataFields */
        $instanceMetadataFields = $this->instanceMetadataFieldTable->findByAnr($anr);
        foreach ($instanceMetadataFields as $metadataField) {
            $translationLabel = $instanceMetadataFieldsTranslations[$metadataField->getLabelTranslationKey()] ?? null;
            $result[$metadataField->getId()] = [
                'id' => $metadataField->getId(),
                'label' => $translationLabel !== null ? $translationLabel->getValue() : '',
            ];
        }

        return $result;
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return $this->configService->getActiveLanguageCodes()[$anr->getLanguage()];
    }
}
