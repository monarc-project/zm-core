<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Service\ModelService;
use Laminas\View\Model\JsonModel;

class ApiModelsController extends AbstractRestfulController
{
    protected const DEFAULT_LIMIT = 25;

    private ModelService $modelService;

    public function __construct(ModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    public function getList()
    {
        $searchString = $this->params()->fromQuery('filter', '');
        $filter = [];
        $status = $this->params()->fromQuery('status', Model::STATUS_ACTIVE);
        if ($status !== 'all') {
            $filter['status'] = (int)$status;
        }
        $isGeneric = $this->params()->fromQuery('isGeneric');
        if ($isGeneric !== null) {
            $filter['isGeneric'] = $isGeneric;
        }
        $order = $this->params()->fromQuery('order', '');

        $models = $this->modelService->getList($searchString, $filter, $order);

        $page = $this->params()->fromQuery('page', 1);
        $limit = $this->params()->fromQuery('limit', static::DEFAULT_LIMIT);

        return new JsonModel([
            'count' => \count($models),
            'vulnerabilities' => \array_slice($models, ($page - 1) * $limit, $limit),
        ]);
    }

    public function get($id)
    {
        return new JsonModel($this->modelService->getModelData($id));
    }

    public function create($data)
    {
        // TODO: validate input. inject validator.
        // label1 is mandatory.

        $modelId = $this->modelService->create($data);

        return new JsonModel([
            'status' => 'ok',
            'id' => $modelId,
        ]);
    }

    public function update($id, $data)
    {
        // TODO: validate input. inject validator.

        $this->modelService->update($id, $data);

        return new JsonModel(['status' => 'ok']);
    }

    public function patch($id, $data)
    {
        // TODO: validate input. inject validator.

        $this->modelService->patch($id, $data);

        return new JsonModel(['status' => 'ok']);
    }

    public function delete($id)
    {
        $this->modelService->delete($id);

        return new JsonModel(['status' => 'ok']);
    }

    public function deleteList($data)
    {
        $this->modelService->deleteList($data);

        return new JsonModel(['status' => 'ok']);
    }
}
