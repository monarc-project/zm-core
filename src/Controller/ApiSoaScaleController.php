<?php declare(strict_types=1);

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\SoaScaleService;

class ApiSoaScaleController extends AbstractRestfulController
{
    private SoaScaleService $soaScaleService;

    public function __construct(SoaScaleService $soaScaleService)
    {
        $this->soaScaleService = $soaScaleService;
    }

    public function getList()
    {
        $anrId = (int) $this->params()->fromRoute('anrId');
        $language = $this->params()->fromQuery("language");

        return new JsonModel([
            'data' => $this->soaScaleService->getSoaScale($anrId, $language),
        ]);
    }

    public function create($data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');

        return new JsonModel([
            'status' => 'ok',
            'id' => $this->soaScaleService->createSoaScaleType($anrId, $data),
        ]);
    }

    public function update($id, $data)
    {
        $data['anr'] = (int)$this->params()->fromRoute('anrId');

        if ($this->soaScaleService->update((int)$id, $data)) {
            return new JsonModel(['status' => 'ok']);
        }

        return new JsonModel(['status' => 'ko']);
    }
}
