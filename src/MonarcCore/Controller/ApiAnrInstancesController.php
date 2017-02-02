<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

use MonarcCore\Model\Entity\Instance;
use MonarcCore\Service\InstanceService;
use Zend\View\Model\JsonModel;

/**
 * Api Anr Instances Controller
 *
 * Class ApiAnrInstancesController
 * @package MonarcCore\Controller
 */
class ApiAnrInstancesController extends AbstractController
{
    protected $name = 'instances';
    protected $dependencies = ['anr', 'asset', 'object', 'root', 'parent'];

    /**
     * Get List
     *
     * @return JsonModel
     */
    public function getList()
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $instances = $service->findByAnr($anrId);
        return new JsonModel(array(
            $this->name => $instances
        ));
    }

    /**
     * Update
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function update($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $service->updateInstance($anrId, $id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Patch
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function patch($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $service->patchInstance($anrId, $id, $data, [], false);

        return new JsonModel(array('status' => 'ok'));
    }


    public function get($id)
    {
        $anrId = (int) $this->params()->fromRoute('anrid');

        /** @var InstanceService $service */
        $service = $this->getService();
        $entity = $service->getEntityByIdAndAnr($id, $anrId);

        if (count($this->dependencies)) {
            $this->formatDependencies($entity, $this->dependencies);
        }

        return new JsonModel($entity);
    }

    public function exportAction()
    {
        /** @var InstanceService $service */
        $service = $this->getService();

        $id = $this->params()->fromRoute('id');
        $data = ['id' => $id];

        $response = $this->getResponse();
        $response->setContent($service->export($data));

        $headers = $response->getHeaders();
        $headers->clearHeaders()
            ->addHeaderLine('Content-Type', 'text/plain; charset=utf-8')
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . (empty($data['filename'])?$data['id']:$data['filename']) . '.bin"');

        return $this->response;
    }

    /**
     * Create
     *
     * @param mixed $data
     * @return JsonModel
     * @throws \Exception
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
            throw new \Exception(implode(', ', $missing), 412);
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

