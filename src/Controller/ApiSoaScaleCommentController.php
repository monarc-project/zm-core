<?php declare(strict_types=1);

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\SoaScaleCommentService;

class ApiSoaScaleCommentController extends AbstractRestfulController
{
    private SoaScaleCommentService $soaScaleCommentService;

    public function __construct(SoaScaleCommentService $soaScaleCommentService)
    {
        $this->soaScaleCommentService = $soaScaleCommentService;
    }

    public function getList()
    {
        $anrId = (int) $this->params()->fromRoute('anrId');
        $language = $this->params()->fromQuery("language");

        return new JsonModel([
            'data' => $this->soaScaleCommentService->getSoaScaleComments($anrId, $language),
        ]);
    }
}
