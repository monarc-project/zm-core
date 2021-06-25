<?php declare(strict_types=1);

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Model\Table\TranslationTable;

class OperationalRiskScaleCommentService
{
    private AnrTable $anrTable;

    private OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    private TranslationTable $translationTable;

    private ConfigService $configService;

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
        $anr = $this->anrTable->findById($data['anr']);

        /** @var OperationalRiskScaleComment|null $operationalRiskScaleComment */
        $operationalRiskScaleComment = $this->operationalRiskScaleCommentTable->findById($id);
        if ($operationalRiskScaleComment === null) {
            throw new EntityNotFoundException(sprintf('Operational risk scale comment ID (%d) does not exist,', $id));
        }

        if (isset($data['scaleValue'])) {
            $operationalRiskScaleComment->setScaleValue((int)$data['scaleValue']);
        }
        if (!empty($data['comment']) && !empty($data['lang'])) {
            $translationKey = $operationalRiskScaleComment->getCommentTranslationKey();
            $translation = $this->translationTable->findByKeyAndLanguage($translationKey, $data['lang']);
            $translation->setValue($data['comment']);
            $this->translationTable->save($translation, false);
        }
        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment);

        return $operationalRiskScaleComment->getId();
    }
}
