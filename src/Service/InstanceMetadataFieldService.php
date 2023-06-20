<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\InstanceMetadataFieldSuperClass;
use Monarc\Core\Model\Entity\InstanceMetadataField;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Table\InstanceMetadataFieldTable;
use Monarc\Core\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class InstanceMetadataFieldService
{
    protected InstanceMetadataFieldTable $instanceMetadataFieldTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    protected UserSuperClass $connectedUser;

    public function __construct(
        InstanceMetadataFieldTable $instanceMetadataFieldTable,
        TranslationTable $translationTable,
        ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->instanceMetadataFieldTable = $instanceMetadataFieldTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function create(Anr $anr, array $data): array
    {
        $returnValue = [];
        $data = $data['metadataField'] ?? $data;
        foreach ($data as $inputMetadata) {
            $metadataField = (new InstanceMetadataField())
                ->setAnr($anr)
                ->setLabelTranslationKey((string)Uuid::uuid4())
                ->setCreator($this->connectedUser->getEmail());

            $this->instanceMetadataFieldTable->save($metadataField);
            $returnValue[] = $metadataField->getId();

            foreach ($inputMetadata as $lang => $labelText) {
                $translation = $this->createTranslationObject(
                    $anr,
                    TranslationSuperClass::INSTANCE_METADATA,
                    $metadataField->getLabelTranslationKey(),
                    $lang,
                    (string)$labelText
                );
                $this->translationTable->save($translation);
            }
        }

        return $returnValue;
    }

    public function getList(Anr $anr, string $language = null): array
    {
        $result = [];
        /** @var InstanceMetadataFieldSuperClass[] $metadataFields */
        $metadataFields = $this->instanceMetadataFieldTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::INSTANCE_METADATA],
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

    public function delete(AnrSuperClass $anr, int $id): void
    {
        /** @var InstanceMetadataFieldSuperClass $metadataToDelete */
        $metadataToDelete = $this->instanceMetadataFieldTable->findByIdAndAnr($id, $anr);

        $translationsToRemove = $this->translationTable
            ->findByAnrAndKey($anr, $metadataToDelete->getLabelTranslationKey());
        foreach ($translationsToRemove as $translationToRemove) {
            $this->translationTable->remove($translationToRemove, false);
        }

        $this->instanceMetadataFieldTable->remove($metadataToDelete);
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

    public function getInstanceMetadataField(Anr $anr, int $id, string $language): array
    {
        /** @var InstanceMetadataFieldSuperClass $metadata */
        $metadata = $this->instanceMetadataFieldTable->findByIdAndAnr($id, $anr);
        if ($language === '') {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [TranslationSuperClass::INSTANCE_METADATA],
            $language
        );

        $translationLabel = $translations[$metadata->getLabelTranslationKey()] ?? null;

        return [
            'id' => $metadata->getId(),
            $language => $translationLabel !== null ? $translationLabel->getValue() : '',
        ];
    }

    public function update(AnrSuperClass $anr, int $id, array $data): InstanceMetadataFieldSuperClass
    {
        /** @var InstanceMetadataFieldSuperClass $metadata */
        $metadata = $this->instanceMetadataFieldTable->findByIdAndAnr($id, $anr);
        $languageCode = $data['language'] ?? $this->getAnrLanguageCode($metadata->getAnr());
        if (!empty($data[$languageCode])) {
            $translationKey = $metadata->getLabelTranslationKey();
            if (!empty($translationKey)) {
                $translation = $this->translationTable
                    ->findByAnrKeyAndLanguage($metadata->getAnr(), $translationKey, $languageCode);
                $translation->setValue($data[$languageCode]);
                $this->translationTable->save($translation, false);
            }
        }
        $this->instanceMetadataFieldTable->save($metadata);

        return $metadata;
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        throw new \LogicException('The "Core\Anr" entity does not have a language field.');
    }
}
