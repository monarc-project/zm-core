<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\ModelService;
use Laminas\View\Model\JsonModel;

class ApiModelsDuplicationController extends AbstractRestfulController
{
    private ModelService $modelService;

    public function __construct(ModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    public function create($data)
    {
        if (empty($data['model'])) {
            throw new Exception('"model" param is missing', 412);
        }

        $id = $this->modelService->duplicate((int)$data['model']);

        return new JsonModel([
            'status' => 'ok',
            'id' => $id,
        ]);
    }
}
