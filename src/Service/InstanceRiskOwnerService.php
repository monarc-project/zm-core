<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Table\InstanceRiskOwnerTable;

class InstanceRiskOwnerService
{
    private InstanceRiskOwnerTable $instanceRiskOwnerTable;

    public function __construct(InstanceRiskOwnerTable $instanceRiskOwnerTable)
    {
        $this->instanceRiskOwnerTable = $instanceRiskOwnerTable;
    }

    public function getList(Anr $anr, array $params = []): array
    {
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
