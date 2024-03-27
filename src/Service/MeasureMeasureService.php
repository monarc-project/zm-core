<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\MeasureMeasure;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Table\MeasureTable;
use Monarc\Core\Table\MeasureMeasureTable;

class MeasureMeasureService
{
    public function __construct(private MeasureMeasureTable $measureMeasureTable, private MeasureTable $measureTable)
    {
    }

    public function getList()
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

    public function create(array $data, bool $saveInDb = true)
    {
        if ($data['masterMeasureUuid'] === $data['linkedMeasureUuid']) {
            throw new Exception('It is not possible to link a control to itself', 412);
        }

        $masterMeasure = $this->measureTable->findByUuid($data['masterMeasureUuid']);
        $linkedMeasure = $this->measureTable->findByUuid($data['linkedMeasureUuid']);
        $masterMeasure->addLinkedMeasure($linkedMeasure);

        return $this->measureTable->save($masterMeasure, $saveInDb);
    }

    public function createList(array $data)
    {
        foreach ($data as $rowData) {
            $this->create($rowData, false);
        }

        $this->measureMeasureTable->flush();
    }

    public function delete(string $masterMeasureUuid, string $linkedMeasureUuid)
    {
        $masterMeasure = $this->measureTable->findByUuid($masterMeasureUuid);
        $linkedMeasure = $this->measureTable->findByUuid($linkedMeasureUuid);
        $masterMeasure->removeLinkedMeasure($linkedMeasure);

        $this->measureTable->saveEntity($masterMeasure);
    }
}
