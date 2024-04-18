<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\RolfTag;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\RolfTagTable;

class RolfTagService
{
    private UserSuperClass $connectedUser;

    public function __construct(private RolfTagTable $rolfTagTable, ConnectedUserService $connectedUserService)
    {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $formattedInputParams): array
    {
        $result = [];
        /** @var RolfTag $rolfTag */
        foreach ($this->rolfTagTable->findByParams($formattedInputParams) as $rolfTag) {
            $result[] = $this->prepareRolfTagData($rolfTag);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $formattedInputParams): int
    {
        return $this->rolfTagTable->countByParams($formattedInputParams);
    }

    public function getRolfTagData(int $id): array
    {
        /** @var RolfTag $rolfTag */
        $rolfTag = $this->rolfTagTable->findById($id);

        return $this->prepareRolfTagData($rolfTag);
    }

    public function create(array $data, bool $saveInDb = true): RolfTag
    {
        /** @var RolfTag $rolfTag */
        $rolfTag = (new RolfTag())
            ->setCode($data['code'])
            ->setLabels($data)
            ->setCreator($this->connectedUser->getEmail());

        $this->rolfTagTable->save($rolfTag, $saveInDb);

        return $rolfTag;
    }

    public function createList(array $data): array
    {
        $createdRowsNumbers = [];
        foreach ($data as $rowNum => $rowData) {
            $this->create($rowData, false);
            $createdRowsNumbers[] = $rowNum;
        }

        return $createdRowsNumbers;
    }

    public function update(int $id, array $data): RolfTag
    {
        /** @var RolfTag $rolfTag */
        $rolfTag = $this->rolfTagTable->findById($id);

        $rolfTag->setCode($data['code'])->setLabels($data)->setUpdater($this->connectedUser->getEmail());

        $this->rolfTagTable->save($rolfTag);

        return $rolfTag;
    }

    public function delete(int $id): void
    {
        /** @var RolfTag $rolfTag */
        $rolfTag = $this->rolfTagTable->findById($id);

        $this->rolfTagTable->remove($rolfTag);
    }

    public function deleteList(array $data): void
    {
        $this->rolfTagTable->removeList($this->rolfTagTable->findByIds($data));
    }

    private function prepareRolfTagData(RolfTag $rolfTag): array
    {
        return array_merge([
            'id' => $rolfTag->getId(),
            'code' => $rolfTag->getCode(),
        ], $rolfTag->getLabels());
    }
}
