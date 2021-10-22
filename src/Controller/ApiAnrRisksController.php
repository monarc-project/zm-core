<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Service\InstanceRiskService;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * Api Anr Risks Controller
 *
 * Class ApiAnrRisksController
 * @package Monarc\Core\Controller
 */
class ApiAnrRisksController extends AbstractRestfulController
{
    private InstanceRiskService $instanceRiskService;

    public function __construct(InstanceRiskService $instanceRiskService)
    {
        $this->instanceRiskService = $instanceRiskService;
    }

    public function get($id)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $params = $this->prepareParams();

        $risks = $this->instanceRiskService->getInstanceRisks($anrId, $id, $params);

        return new JsonModel([
            'count' => \count($risks),
            'risks' => $params['limit'] > 0 ?
                \array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
                : $risks,
        ]);
    }

    public function getList()
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $params = $this->prepareParams();

        $risks = $this->instanceRiskService->getInstanceRisks($anrId, null, $params);

        return new JsonModel([
            'count' => \count($risks),
            'risks' => $params['limit'] > 0 ?
                \array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit'])
                : $risks,
        ]);
    }

    protected function prepareParams(): array
    {
        $params = $this->params();

        return [
            'keywords' => $params->fromQuery('keywords'),
            'kindOfMeasure' => $params->fromQuery('kindOfMeasure'),
            'order' => $params->fromQuery('order', 'maxRisk'),
            'order_direction' => $params->fromQuery('order_direction', 'desc'),
            'thresholds' => $params->fromQuery('thresholds'),
            'page' => $params->fromQuery('page', 1),
            'limit' => $params->fromQuery('limit', 50)
        ];
    }
}
