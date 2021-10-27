<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Service\InstanceService;
use Laminas\View\Model\JsonModel;

/**
 * Api Anr Instances Controller
 *
 * Class ApiAnrInstancesController
 * @package Monarc\Core\Controller
 */
class ApiAnrInstancesController extends AbstractController
{
    protected $name = 'instances';
    protected $dependencies = ['anr', 'asset', 'object', 'root', 'parent'];

    /**
     * @inheritdoc
     */
    public function getList()
    {
        $anrId = (int)$this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $instances = $service->getInstancesData($anrId);

        return new JsonModel(array(
            $this->name => $instances
        ));
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $service->updateInstance($anrId, $id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');
        $data['anr'] = $anrId ;
        /** @var InstanceService $service */
        $service = $this->getService();
        $service->patchInstance($anrId, $id, $data, [], false);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $anrId = (int)$this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $entity = $service->getInstanceData($id, $anrId);

        if (count($this->dependencies)) {
            $this->formatDependencies($entity, $this->dependencies);
        }

        return new JsonModel($entity);
    }

    /**
     * TODO: the export with password and evals doesn't work as far as we need to get [POST] data, but request is [GET]
     *
     * Exports an instance in our own custom encrypted format and downloads it to the client browser
     * @return \Laminas\Stdlib\ResponseInterface The file attachment response
     */
    public function exportAction()
    {
        /** @var InstanceService $service */
        $service = $this->getService();

        $id = $this->params()->fromRoute('id');
        $data['id'] = $id;

        if (empty($data['password'])) {
            $contentType = 'application/json; charset=utf-8';
            $extension = '.json';
        } else {
            $contentType = 'text/plain; charset=utf-8';
            $extension = '.bin';
        }

        $exportData = $service->export($data);
        $this->getResponse()
            ->setContent($exportData);

        $this->getResponse()
            ->getHeaders()
            ->clearHeaders()
            ->addHeaderLine('Content-Type', $contentType)
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' .
                (empty($data['filename']) ? $data['id'] : $data['filename']) . $extension . '"');

        return $this->getResponse();
    }

    /**
     * @inheritdoc
     */
    public function create($data)
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        //verification required
        $required = ['object', 'parent', 'position'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $missing[] = $field . ' missing';
            }
        }
        if (count($missing)) {
            throw new Exception(implode(', ', $missing), 412);
        }

        $data['c'] = isset($data['c'])?$data['c']:'-1';
        $data['i'] = isset($data['i'])?$data['i']:'-1';
        $data['d'] = isset($data['d'])?$data['d']:'-1';

        /** @var InstanceService $service */
        $service = $this->getService();
        $id = $service->instantiateObjectToAnr($anrId, $data, true, true, Instance::MODE_CREA_ROOT);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }
}
