<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Service\InstanceRiskOpService;

class ApiAnrInstancesRisksOpController extends AbstractRestfulController
{
    private InstanceRiskOpService $instanceRiskOpService;

    public function __construct(InstanceRiskOpService $instanceRiskOpService)
    {
        $this->instanceRiskOpService = $instanceRiskOpService;
    }

    public function update($id, $data)
    {
        $risk = $this->instanceRiskOpService->update($id, $data);
        unset($risk['anr'], $risk['instance'], $risk['object'], $risk['rolfRisk']);

        return new JsonModel($risk);
    }
}
