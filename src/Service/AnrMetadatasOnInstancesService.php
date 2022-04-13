<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\AnrMetadatasOnInstancesSuperClass;
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

    public function __construct(
        AnrTable $anrTable,
        AnrMetadatasOnInstancesTable $anrMetadatasOnInstancesTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->anrMetadatasOnInstancesTable = $anrMetadatasOnInstancesTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
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
        $languageCodes = $this->getLanguageCodesForTranslations($anr);

        foreach ($data as $inputMetadata) {
            $metadata = (new AnrMetadatasOnInstances())
                ->setAnr($anr)
                ->setLabelTranslationKey((string)Uuid::uuid4())
                ->setCreator($this->connectedUser->getEmail());

            $this->anrMetadatasOnInstancesTable->save($metadata);
            $returnValue[] = $metadata->getId();

            foreach ($languageCodes as $languageCode) {
                if (isset($inputMetadata[$languageCode])) {
                    $translation = $this->createTranslationObject(
                        $anr,
                        Translation::ANR_METADATAS_ON_INSTANCES,
                        $metadata->getLabelTranslationKey(),
                        $languageCode,
                        $inputMetadata[$languageCode]
                    );
                    $this->translationTable->save($translation);
                }
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
            $translationComment = $translations[$metadata->getLabelTranslationKey()] ?? null;
            $result= [
                'id' => $metaDatas->getId(),
                'label' => $translationComment !== null ? $translationComment->getValue() : '',
            ];
        }

        return $result;
    }
}
