<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\OperationalRiskScaleComment;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\OperationalRiskScaleCommentTable;
use Monarc\Core\Table\TranslationTable;

class OperationalRiskScaleCommentService
{
    private OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable;

    private TranslationTable $translationTable;

    private UserSuperClass $connectedUser;

    public function __construct(
        OperationalRiskScaleCommentTable $operationalRiskScaleCommentTable,
        TranslationTable $translationTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->operationalRiskScaleCommentTable = $operationalRiskScaleCommentTable;
        $this->translationTable = $translationTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function update(int $id, array $data): OperationalRiskScaleComment
    {
        /** @var OperationalRiskScaleComment $operationalRiskScaleComment */
        $operationalRiskScaleComment = $this->operationalRiskScaleCommentTable->findById($id);

        if (isset($data['scaleValue'])) {
            $operationalRiskScaleComment->setScaleValue((int)$data['scaleValue']);
        }

        if (isset($data['comment'])) {
            $translation = $this->translationTable->findByAnrKeyAndLanguage(
                $operationalRiskScaleComment->getAnr(),
                $operationalRiskScaleComment->getLabelTranslationKey(),
                $data['language'] ?? 'fr'
            );
            $translation->setValue($data['comment'])->setUpdater($this->connectedUser->getEmail());

            $this->translationTable->save($translation, false);
        }
        $this->operationalRiskScaleCommentTable->save($operationalRiskScaleComment);

        return $operationalRiskScaleComment;
    }
}
