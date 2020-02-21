<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Service\InstanceService;
use Laminas\Http\Response;
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
    /** @var InstanceService */
    private $instanceService;

    public function __construct(InstanceService $instanceService)
    {
        $this->instanceService = $instanceService;
    }

    public function get($id)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $params = $this->prepareParams();

        if ($this->params()->fromQuery('csv', false)) {
            /** @var Response $response */
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
            $response->setContent($this->instanceService->getCsvRisks($anrId, ['id' => $id], $params));

            return $response;
        }

        $risks = $this->instanceService->getRisks($anrId, ['id' => $id], $params);

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

        if ($this->params()->fromQuery('csv', false)) {
            /** @var Response $response */
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
            $response->setContent($this->instanceService->getCsvRisks($anrId, null, $params));

            return $response;
        }

        $risks = $this->instanceService->getRisks($anrId, null, $params);

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
