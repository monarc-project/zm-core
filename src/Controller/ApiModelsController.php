<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Monarc\Core\Controller\Handler\ControllerRequestResponseHandlerTrait;
use Monarc\Core\InputFormatter\Model\GetModelsInputFormatter;
use Monarc\Core\Service\ModelService;
use Monarc\Core\Validator\InputValidator\Model\PostModelDataInputValidator;

class ApiModelsController extends AbstractRestfulController
{
    use ControllerRequestResponseHandlerTrait;

    private GetModelsInputFormatter $getModelsInputFormatter;

    private PostModelDataInputValidator $postModelDataInputValidator;

    private ModelService $modelService;

    public function __construct(
        GetModelsInputFormatter $getModelsInputFormatter,
        PostModelDataInputValidator $postModelDataInputValidator,
        ModelService $modelService
    ) {
        $this->getModelsInputFormatter = $getModelsInputFormatter;
        $this->postModelDataInputValidator = $postModelDataInputValidator;
        $this->modelService = $modelService;
    }

    public function getList()
    {
        $formattedParams = $this->getFormattedInputParams($this->getModelsInputFormatter);

        return $this->getPreparedJsonResponse([
            'count' => $this->modelService->getCount($formattedParams),
            'models' => $this->modelService->getList($formattedParams),
        ]);
    }

    public function get($id)
    {
        return $this->getPreparedJsonResponse($this->modelService->getModelData((int)$id));
    }

    public function create($data)
    {
        $this->validatePostParams($this->postModelDataInputValidator, $data);

        $modelId = $this->modelService->create($data);

        return $this->getPreparedJsonResponse([
            'status' => 'ok',
            'id' => $modelId,
        ]);
    }

    public function update($id, $data)
    {
        $this->validatePostParams($this->postModelDataInputValidator, $data);

        $this->modelService->update((int)$id, $data);

        return $this->getPreparedJsonResponse(['status' => 'ok']);
    }

    public function patch($id, $data)
    {
        $this->modelService->patch((int)$id, $data);

        return $this->getPreparedJsonResponse(['status' => 'ok']);
    }

    public function delete($id)
    {
        $this->modelService->delete((int)$id);

        return $this->getPreparedJsonResponse(['status' => 'ok']);
    }

    public function deleteList($data)
    {
        $this->modelService->deleteList($data);

        return $this->getPreparedJsonResponse(['status' => 'ok']);
    }
}
