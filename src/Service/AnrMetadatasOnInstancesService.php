<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstancesSuperClass;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstances;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\TranslationSuperClass;
use Monarc\Core\Model\Entity\Translation;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\AnrMetadatasOnInstancesTable;
use Monarc\Core\Model\Table\TranslationTable;
use Ramsey\Uuid\Uuid;

class AnrMetadatasOnInstancesService
{
    protected AnrTable $anrTable;

    protected AnrMetadatasOnInstancesTable $anrMetadatasOnInstancesTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    protected UserSuperClass $connectedUser;

    public function __construct(
        AnrTable $anrTable,
        AnrMetadatasOnInstancesTable $anrMetadatasOnInstancesTable,
        TranslationTable $translationTable,
        ConfigService $configService,
        ConnectedUserService $connectedUserService
    ) {
        $this->anrTable = $anrTable;
        $this->anrMetadatasOnInstancesTable = $anrMetadatasOnInstancesTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    /**
     * @param int $anrId
     * @param array $data
     *
     * @return array
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createAnrMetadatasOnInstances(int $anrId, array $data): array
    {
        $anr = $this->anrTable->findById($anrId);
        $returnValue = [];

        foreach ($data as $inputMetadata) {
            $metadata = (new AnrMetadatasOnInstances())
                ->setAnr($anr)
                ->setLabelTranslationKey((string)Uuid::uuid4())
                ->setCreator($this->connectedUser->getEmail());

            $this->anrMetadatasOnInstancesTable->save($metadata);
            $returnValue[] = $metadata->getId();

            foreach ($inputMetadata as $lang => $labelText) {
                $translation = $this->createTranslationObject(
                    $anr,
                    Translation::ANR_METADATAS_ON_INSTANCES,
                    $metadata->getLabelTranslationKey(),
                    $lang,
                    (string)$labelText
                );
                $this->translationTable->save($translation);
            }
        }

        return $returnValue;
    }

    /**
     * @param int $anrId
     * @param string $language
     *
     * @return array
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getAnrMetadatasOnInstances(int $anrId, string $language = null): array
    {
        $result = [];
        $anr = $this->anrTable->findById($anrId);
        $metaDatas = $this->anrMetadatasOnInstancesTable->findByAnr($anr);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::ANR_METADATAS_ON_INSTANCES],
            $language
        );

        foreach ($metaDatas as $metadata) {
            $translationLabel = $translations[$metadata->getLabelTranslationKey()] ?? null;
            $result[]= [
                'id' => $metadata->getId(),
                'label' => $translationLabel !== null ? $translationLabel->getValue() : '',
            ];
        }

        return $result;
    }

    /**
     * @param int $id
     *
     * @throws EntityNotFoundException
     */
    public function deleteMetadataOnInstances(int $id): void
    {
        $metadataToDelete = $this->anrMetadatasOnInstancesTable->findById($id);
        if ($metadataToDelete === null) {
            throw new EntityNotFoundException(sprintf('Metadata with ID %d is not found', $id));
        }

        $this->anrMetadatasOnInstancesTable->remove($metadataToDelete);

        $translationsKeys[] = $metadataToDelete->getLabelTranslationKey();

        if (!empty($translationsKeys)) {
            $this->translationTable->deleteListByKeys($translationsKeys);
        }
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

    public function getAnrMetadataOnInstance(int $anrId, int $id, string $language)
    {
        $anr = $this->anrTable->findById($anrId);
        $metaDatas = $this->anrMetadatasOnInstancesTable->findByAnr($anrId, $id);
        if ($language === null) {
            $language = $this->getAnrLanguageCode($anr);
        }

        $translations = $this->translationTable->findByAnrTypesAndLanguageIndexedByKey(
            $anr,
            [Translation::ANR_METADATAS_ON_INSTANCES],
            $language
        );

        $translationLabel = $translations[$metadata->getLabelTranslationKey()] ?? null;
        return [
            'id' => $metadata->getId(),
            'label' => $translationLabel !== null ? $translationLabel->getValue() : '',
        ];
    }

    protected function getAnrLanguageCode(AnrSuperClass $anr): string
    {
        return $this->configService->getActiveLanguageCodes()[$anr->getLanguage()];
    }
}
