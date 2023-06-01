<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleCommentSuperClass;
use Monarc\Core\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Table\TranslationTable;

class OperationalRiskScaleCommentService
{
    protected OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    protected TranslationTable $translationTable;

    protected ConfigService $configService;

    public function __construct(
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConfigService $configService
    ) {
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->configService = $configService;
    }

    public function update(int $id, array $data): int
    {
        /** @var OperationalRiskScaleCommentSuperClass $operationalRiskScaleComment */
        $operationalRiskScaleComment = $this->operationalRiskScaleCommentTable->findById($id);

        if (isset($data['scaleValue'])) {
            $operationalRiskScaleComment->setScaleValue((int)$data['scaleValue']);
        }

        if (!empty($data['comment'])) {
            $translation = $this->translationTable->findByAnrKeyAndLanguage(
                $operationalRiskScaleComment->getAnr(),
                $operationalRiskScaleComment->getCommentTranslationKey(),
                $this->getLanguageCode($operationalRiskScaleComment->getAnr(), $data)
            );
            $translation->setValue($data['comment']);
            $this->translationTable->save($translation, false);
        }
        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment);

        return $operationalRiskScaleComment->getId();
    }

    protected function getLanguageCode(AnrSuperClass $anr, array $data): string
    {
        return $data['language'] ?? 'fr';
    }
}
