<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\Anr;
use Monarc\Core\Entity\AnrInstanceMetadataField;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\TranslationSuperClass;
use Monarc\Core\Entity\Translation;
use Monarc\Core\Table\AnrInstanceMetadataFieldTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class AnrInstanceMetadataFieldService
{
    protected AnrInstanceMetadataFieldTable $anrInstanceMetadataFieldTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    protected UserSuperClass $connectedUser;

    public function __construct(
        AnrInstanceMetadataFieldTable $anrInstanceMetadataFieldTable,
        TranslationTable $translationTable,
        ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->anrInstanceMetadataFieldTable = $anrInstanceMetadataFieldTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(Anr $anr, string $language): array
    {
        $result = [];
        /** @var AnrInstanceMetadataField[] $metadataFields */
        $metadataFields = $this->anrInstanceMetadataFieldTable->findByAnr($anr);

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::ANR_INSTANCE_METADATA_FIELD],
            $language
        );

        foreach ($metadataFields as $index => $metadataField) {
            $translationLabel = $translations[$metadataField->getLabelTranslationKey()] ?? null;
            $result[] = [
                'id' => $metadataField->getId(),
                'index' => $index + 1,
                $language => $translationLabel !== null ? $translationLabel->getValue() : '',
            ];
        }

        return $result;
    }

    public function getAnrInstanceMetadataField(Anr $anr, int $id, string $language): array
    {
        /** @var AnrInstanceMetadataField $metadataField */
        $metadataField = $this->anrInstanceMetadataFieldTable->findByIdAndAnr($id, $anr);

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::ANR_INSTANCE_METADATA_FIELD],
            $language
        );

        $translationLabel = $translations[$metadataField->getLabelTranslationKey()] ?? null;

        return [
            'id' => $metadataField->getId(),
            $language => $translationLabel !== null ? $translationLabel->getValue() : '',
        ];
    }

    public function create(Anr $anr, array $data, bool $saveInDb = true): AnrInstanceMetadataField
    {
        $metadataFieldData = current($data['metadataField']);
        /** @var AnrInstanceMetadataField $metadataField */
        $metadataField = (new AnrInstanceMetadataField())
            ->setLabelTranslationKey((string)Uuid::uuid4())
            ->setAnr($anr)
            ->setCreator($this->connectedUser->getEmail());

        foreach ($metadataFieldData as $lang => $labelText) {
            $translation = (new Translation())
                ->setAnr($anr)
                ->setType(TranslationSuperClass::ANR_INSTANCE_METADATA_FIELD)
                ->setKey($metadataField->getLabelTranslationKey())
                ->setLang($lang)
                ->setValue((string)$labelText)
                ->setCreator($this->connectedUser->getEmail());

            $this->translationTable->save($translation, false);
        }

        $this->anrInstanceMetadataFieldTable->save($metadataField);

        return $metadataField;
    }

    public function delete(Anr $anr, int $id): void
    {
        /** @var AnrInstanceMetadataField $metadataToDelete */
        $metadataToDelete = $this->anrInstanceMetadataFieldTable->findByIdAndAnr($id, $anr);

        $translationsToRemove = $this->translationTable
            ->findByAnrAndKey($anr, $metadataToDelete->getLabelTranslationKey());
        foreach ($translationsToRemove as $translationToRemove) {
            $this->translationTable->remove($translationToRemove, false);
        }

        $this->anrInstanceMetadataFieldTable->remove($metadataToDelete);
    }

    public function update(Anr $anr, int $id, array $data): AnrInstanceMetadataField
    {
        /** @var AnrInstanceMetadataField $metadata */
        $metadata = $this->anrInstanceMetadataFieldTable->findByIdAndAnr($id, $anr);
        $languageCode = $data['language'];
        if (!empty($data[$languageCode])) {
            $translationKey = $metadata->getLabelTranslationKey();

            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($metadata->getAnr(), $translationKey, $languageCode);
                $translation->setValue($data[$languageCode]);

                $this->translationTable->save($translation);
            }
        }

        return $metadata;
    }
}
