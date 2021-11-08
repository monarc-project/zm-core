<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Table\InstanceRiskOwnerTable;

class InstanceRiskOwnerService
{
    private AnrTable $anrTable;

    private InstanceRiskOwnerTable $instanceRiskOwnerTable;

    public function __construct(AnrTable $anrTable, InstanceRiskOwnerTable $instanceRiskOwnerTable)
    {
        $this->anrTable = $anrTable;
        $this->instanceRiskOwnerTable = $instanceRiskOwnerTable;
    }

    public function getList(int $anrId, array $params = []): array
    {
        $anr = $this->anrTable->findById($anrId);
        $result = [];
        foreach ($this->instanceRiskOwnerTable->findByAnrAndFilterParams($anr, $params) as $instanceRiskOwner) {
            $result[] = [
                'id' => $instanceRiskOwner->getId(),
                'name' => $instanceRiskOwner->getName(),
            ];
        }

        return $result;
    }
}
