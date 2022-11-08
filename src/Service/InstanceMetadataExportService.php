<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Table\InstanceMetadataTable;
use Monarc\Core\Table\TranslationTable;

class InstanceMetadataExportService
{
    protected InstanceMetadataTable $instanceMetadataTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        InstanceMetadataTable $instanceMetadataTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->instanceMetadataTable = $instanceMetadataTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function generateExportArray(AnrSuperClass $anr): array
    {
        $result = [];

        // TODO: we need to fetch the translations without language code for BO and handle it differently later on.
        $instanceMetadataTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::INSTANCE_METADATA],
            $this->getAnrLanguageCode($anr)
        );

        $instanceMetadataList = $this->instanceMetadataTable->findByAnr($anr);
        foreach ($instanceMetadataList as $metadata) {
            $translationLabel = $instanceMetadataTranslations[$metadata->getLabelTranslationKey()] ?? null;
            $result[$metadata->getId()] = [
                'id' => $metadata->getId(),
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
