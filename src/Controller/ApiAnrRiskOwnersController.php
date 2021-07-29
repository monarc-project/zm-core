<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Service\InstanceRiskOwnerService;

/**
 * Api ANR Risk Owners Controller
 *
 * Class ApiAnrRiskOwnersController
 * @package Monarc\Core\Controller
 */
class ApiAnrRiskOwnersController extends AbstractRestfulController
{
    private InstanceRiskOwnerService $instanceRiskOwnerService;

    public function __construct(InstanceRiskOwnerService $instanceRiskOwnerService)
    {
        $this->instanceRiskOwnerService = $instanceRiskOwnerService;
    }

    public function getList()
    {
        $anrId = (int)$this->params()->fromRoute('anrid');

        $instanceRiskOwners = $this->instanceRiskOwnerService->getList($anrId, $this->prepareParams());

        return new JsonModel([
            'instanceRiskOwners' => $instanceRiskOwners,
            'count' => \count($instanceRiskOwners),
        ]);
    }

    protected function prepareParams(): array
    {
        return [
            'name' => $this->params()->fromQuery('filter', ''),
        ];
    }
}
