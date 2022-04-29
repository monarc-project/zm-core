<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstancesSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Table\AnrMetadatasOnInstancesTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class AnrMetadatasOnInstancesExportService
{
    protected AnrMetadatasOnInstancesTable $anrMetadatasOnInstancesTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        AnrMetadatasOnInstancesTable $anrMetadatasOnInstancesTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrMetadatasOnInstancesTable = $anrMetadatasOnInstancesTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function generateExportArray(AnrSuperClass $anr): array
    {
        $result = [];

        // TODO: we need to fetch the translations without language code for BO and handle it differently later on.
        $anrMetadatasOnInstancesTranslations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::ANR_METADATAS_ON_INSTANCES],
            $this->getAnrLanguageCode($anr)
        );

        $AnrMetadatasOnInstances = $this->anrMetadatasOnInstancesTable->findByAnr($anr);
        foreach ($AnrMetadatasOnInstances as $metadata) {
            $translationLabel = $anrMetadatasOnInstancesTranslations[$metadata->getLabelTranslationKey()] ?? null;
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
