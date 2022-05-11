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

    public function update($id, $data)
    {
        $data['anr'] = (int)$this->params()->fromRoute('anrId');

        if ($this->soaScaleCommentService->update((int)$id, $data)) {
            return new JsonModel(['status' => 'ok']);
        }

        return new JsonModel(['status' => 'ko']);
    }

    public function patchList($data)
    {
        $anrId = (int)$this->params()->fromRoute('anrId');
        $language = $this->params()->fromQuery("language");
        $this->soaScaleCommentService->createOrHideSoaScaleComment($anrId, $data);
        return new JsonModel([
            'data' => $this->soaScaleCommentService->getSoaScaleComments($anrId, $language),
        ]);
    }
}
