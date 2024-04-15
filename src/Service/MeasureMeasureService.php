<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\Measure;
use Monarc\Core\Entity\MeasureMeasure;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Table\MeasureTable;
use Monarc\Core\Table\MeasureMeasureTable;

class MeasureMeasureService
{
    public function __construct(private MeasureMeasureTable $measureMeasureTable, private MeasureTable $measureTable)
    {
    }

    public function getList(): array
    {
        $result = [];
        /** @var MeasureMeasure $measureMeasure */
        foreach ($this->measureMeasureTable->findAll() as $measureMeasure) {
            $result[] = [
                'masterMeasure' => array_merge([
                    'uuid' => $measureMeasure->getMasterMeasure()->getUuid(),
                    'code' => $measureMeasure->getMasterMeasure()->getCode(),
                ], $measureMeasure->getMasterMeasure()->getLabels()),
                'linkedMeasure' => array_merge([
                    'uuid' => $measureMeasure->getLinkedMeasure()->getUuid(),
                    'code' => $measureMeasure->getLinkedMeasure()->getCode(),
                ], $measureMeasure->getLinkedMeasure()->getLabels()),
            ];
        }

        return $result;
    }

    public function create(array $data, bool $saveInDb = true): Measure
    {
        if ($data['masterMeasureUuid'] === $data['linkedMeasureUuid']) {
            throw new Exception('It is not possible to link a control to itself', 412);
        }

        /** @var Measure $masterMeasure */
        $masterMeasure = $this->measureTable->findByUuid($data['masterMeasureUuid']);
        /** @var Measure $linkedMeasure */
        $linkedMeasure = $this->measureTable->findByUuid($data['linkedMeasureUuid']);

        $masterMeasure->addLinkedMeasure($linkedMeasure);
        $this->measureTable->save($linkedMeasure, $saveInDb);

        return $masterMeasure;
    }

    /**
     * @return string[]
     */
    public function createList(array $data): array
    {
        $createdIds = [];
        foreach ($data as $rowData) {
            $createdIds[] = $this->create($rowData, false)->getUuid();
        }
        $this->measureMeasureTable->flush();

        return $createdIds;
    }

    public function delete(string $masterMeasureUuid, string $linkedMeasureUuid): void
    {
        /** @var Measure $masterMeasure */
        $masterMeasure = $this->measureTable->findByUuid($masterMeasureUuid);
        /** @var Measure $linkedMeasure */
        $linkedMeasure = $this->measureTable->findByUuid($linkedMeasureUuid);
        $masterMeasure->removeLinkedMeasure($linkedMeasure);

        $this->measureTable->save($masterMeasure);
    }
}
