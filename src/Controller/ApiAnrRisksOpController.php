<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Service\InstanceRiskOpService;

/**
 * Api Anr Risks Op Controller
 *
 * Class ApiAnrRisksController
 * @package Monarc\Core\Controller
 */
class ApiAnrRisksOpController extends AbstractRestfulController
{
    private InstanceRiskOpService $instanceRiskOpService;

    public function __construct(InstanceRiskOpService $instanceRiskOpService)
    {
        $this->instanceRiskOpService = $instanceRiskOpService;
    }

    /**
     * @param int $id Instance ID.
     *
     * @return Response|JsonModel
     */
    public function get($id)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            /** @var Response $response */
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
            $response->setContent(
                $this->instanceRiskOpService->getOperationalRisksInCsv($anrId, $id, $params)
            );

            return $response;
        }

        $risks = $this->instanceRiskOpService->getOperationalRisks($anrId, $id, $params);

        return new JsonModel([
            'count' => \count($risks),
            'oprisks' => \array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit']),
        ]);
    }

    public function getList()
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $params = $this->parseParams();

        if ($this->params()->fromQuery('csv', false)) {
            /** @var Response $response */
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
            $response->setContent($this->instanceRiskOpService->getOperationalRisksInCsv($anrId, null, $params));

            return $response;
        }

        $risks = $this->instanceRiskOpService->getOperationalRisks($anrId, null, $params);

        return new JsonModel([
            'count' => \count($risks),
            'oprisks' => \array_slice($risks, ($params['page'] - 1) * $params['limit'], $params['limit']),
        ]);
    }

    protected function parseParams(): array
    {
        return [
            'keywords' => $this->params()->fromQuery("keywords"),
            'kindOfMeasure' => $this->params()->fromQuery("kindOfMeasure"),
            'order' => $this->params()->fromQuery("order", "maxRisk"),
            'order_direction' => $this->params()->fromQuery("order_direction", "desc"),
            'thresholds' => $this->params()->fromQuery("thresholds"),
            'page' => $this->params()->fromQuery("page", 1),
            'limit' => $this->params()->fromQuery("limit", 50),
        ];
    }
}
