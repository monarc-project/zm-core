<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Table;

class ScaleCommentService
{
    private Table\ScaleCommentTable $scaleCommentTable;

    private Table\ScaleTable $scaleTable;

    private Table\ScaleImpactTypeTable $scaleImpactTypeTable;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\ScaleCommentTable $scaleCommentTable,
        Table\ScaleTable $scaleTable,
        Table\ScaleImpactTypeTable $scaleImpactTypeTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->scaleCommentTable = $scaleCommentTable;
        $this->scaleTable = $scaleTable;
        $this->scaleImpactTypeTable = $scaleImpactTypeTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $formattedInputParams): array
    {
        $result = [];
        /** @var Entity\ScaleComment[] $scaleComments */
        $scaleComments = $this->scaleCommentTable->findByParams($formattedInputParams);
        foreach ($scaleComments as $scaleComment) {
            $result[] = array_merge([
                'id' => $scaleComment->getId(),
                'scaleIndex' => $scaleComment->getScaleIndex(),
                'scaleValue' => $scaleComment->getScaleValue(),
                'scaleImpactType' => $scaleComment->getScaleImpactType() === null ? null : [
                    'id' => $scaleComment->getScaleImpactType()->getId(),
                    'type' => $scaleComment->getScaleImpactType()->getType(),
                ]
            ], $scaleComment->getComments());
        }

        return $result;
    }

    public function create(Entity\Anr $anr, array $data): Entity\ScaleComment
    {
        /** @var Entity\Scale $scale */
        $scale = $this->scaleTable->findById($data['scaleId']);

        $scaleComment = (new Entity\ScaleComment())
            ->setAnr($anr)
            ->setScale($scale)
            ->setComments($data)
            ->setScaleIndex($data['scaleIndex'])
            ->setScaleValue($data['scaleValue'])
            ->setCreator($this->connectedUser->getEmail());

        if (isset($data['scaleImpactType'])) {
            /** @var Entity\ScaleImpactType $scaleImpactType */
            $scaleImpactType = $this->scaleImpactTypeTable->findById($data['scaleImpactType']);
            $scaleComment->setScaleImpactType($scaleImpactType);
        }

        $this->scaleCommentTable->save($scaleComment);

        /** @var Entity\ScaleComment $scaleComment */
        return $scaleComment;
    }

    public function update(Entity\Anr $anr, int $id, array $data): Entity\ScaleComment
    {
        /** @var Entity\ScaleComment $scaleComment */
        $scaleComment = $this->scaleCommentTable->findByIdAndAnr($id, $anr);
        $scaleComment->setComments($data)
            ->setUpdater($this->connectedUser->getEmail());

        $this->scaleCommentTable->save($scaleComment);

        return $scaleComment;
    }
}
