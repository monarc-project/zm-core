<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\Referential;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\ReferentialTable;

class ReferentialService
{
    public function __construct(private ReferentialTable $referentialTable)
    {
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];
        /** @var Referential $referential */
        foreach ($this->referentialTable->findByParams($params) as $referential) {
            $result[] = $this->prepareReferentialDataResult($referential);
        }

        return $result;
    }

    public function getReferentialData(string $uuid): array
    {
        /** @var Referential $referential */
        $referential = $this->referentialTable->findByUuid($uuid);

        return $this->prepareReferentialDataResult($referential);
    }

    public function create(array $data, bool $saveInDb = true): Referential
    {
        /** @var Referential $referential */
        $referential = (new Referential())->setLabels($data);

        $this->referentialTable->save($referential, $saveInDb);

        return $referential;
    }

    public function update(string $uuid, array $data): Referential
    {
        /** @var Referential $referential */
        $referential = $this->referentialTable->findByUuid($uuid);

        $referential->setLabels($data);

        $this->referentialTable->save($referential);

        return $referential;
    }

    public function delete(string $uuid): void
    {
        /** @var Referential $referential */
        $referential = $this->referentialTable->findByUuid($uuid);

        $this->referentialTable->remove($referential);
    }

    private function prepareReferentialDataResult(Referential $referential): array
    {
        $measures = [];
        foreach ($referential->getMeasures() as $measure) {
            $measures[] = ['uuid' => $measure->getUuid()];
        }

        return array_merge(['uuid' => $referential->getUuid()], $referential->getLabels(), ['measures' => $measures]);
    }
}
