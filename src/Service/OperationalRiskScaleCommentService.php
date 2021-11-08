<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\OperationalRiskScaleCommentSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Table\TranslationTable;

class OperationalRiskScaleCommentService
{
    protected AnrTable $anrTable;

    protected OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        AnrTable $anrTable,
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->anrTable = $anrTable;
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function update(int $id, array $data): int
    {
        /** @var OperationalRiskScaleCommentSuperClass|null $operationalRiskScaleComment */
        $operationalRiskScaleComment = $this->operationalRiskScaleCommentTable->findById($id);
        if ($operationalRiskScaleComment === null) {
            throw new EntityNotFoundException(sprintf('Operational risk scale comment ID (%d) does not exist,', $id));
        }

        if (isset($data['scaleValue'])) {
            $operationalRiskScaleComment->setScaleValue((int)$data['scaleValue']);
        }

        if (!empty($data['comment'])) {
            $anr = $this->anrTable->findById($data['anr']);
            $languageCode = $data['language'] ?? $this->configService->getActiveLanguageCodes()[$anr->getLanguage()];

            $translationKey = $operationalRiskScaleComment->getCommentTranslationKey();
            $translation = $this->translationTable->findByAnrKeyAndLanguage($anr, $translationKey, $languageCode);
            $translation->setValue($data['comment']);
            $this->translationTable->save($translation, false);
        }
        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment);

        return $operationalRiskScaleComment->getId();
    }
}
