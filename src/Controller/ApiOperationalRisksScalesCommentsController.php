<?php declare(strict_types=1);

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Service\OperationalRiskScaleCommentService;

class ApiOperationalRisksScalesCommentsController extends AbstractRestfulController
{
    private OperationalRiskScaleCommentService $operationalRiskScaleCommentService;

    public function __construct(OperationalRiskScaleCommentService $operationalRiskScaleCommentService)
    {
        $this->operationalRiskScaleCommentService = $operationalRiskScaleCommentService;
    }

    public function update($id, $data)
    {
        $data['anr'] = (int)$this->params()->fromRoute('anrId');

        if ($this->operationalRiskScaleCommentService->update((int)$id, $data)) {
            return new JsonModel(['status' => 'ok']);
        }

        return new JsonModel(['status' => 'ko']);
    }
}
