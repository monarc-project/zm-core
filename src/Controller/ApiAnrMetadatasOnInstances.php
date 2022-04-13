<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Service\AnrMetadatasOnInstancesService;

/**
 * Api Anr Metadatas On Instances Controller
 *
 * Class ApiAnrMetadatasOnInstancesController
 * @package Monarc\Core\Controller
 */
class ApiAnrMetadatasOnInstancesController extends AbstractRestfulController
{

    private AnrMetadatasOnInstancesService $anrMetadatasOnInstancesService;

    public function __construct(AnrMetadatasOnInstancesService $anrMetadatasOnInstancesService)
    {
        $this->anrMetadatasOnInstancesService = $anrMetadatasOnInstancesService;
    }

    public function create($data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');

        return new JsonModel([
            'status' => 'ok',
            'id' => $this->anrMetadatasOnInstancesService->createAnrMetadataOnInstances($anrId, $data),
        ]);
    }
}
